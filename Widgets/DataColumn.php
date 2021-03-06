<?php
namespace exface\Core\Widgets;

use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\Core\Factories\ExpressionFactory;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\Widgets\iShowDataColumn;
use exface\Core\Exceptions\Model\MetaAttributeNotFoundError;
use exface\Core\Widgets\Traits\iCanBeAlignedTrait;
use exface\Core\Exceptions\Widgets\WidgetPropertyInvalidValueError;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\DataTypes\PriceDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Interfaces\Model\AggregatorInterface;
use exface\Core\CommonLogic\Model\Aggregator;
use exface\Core\DataTypes\SortingDirectionsDataType;
use exface\Core\Interfaces\Model\ExpressionInterface;
use exface\Core\Interfaces\Widgets\iTakeInput;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\Interfaces\Widgets\iHaveValue;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Interfaces\Widgets\iCanBeAligned;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\CommonLogic\WidgetDimension;
use exface\Core\Factories\WidgetDimensionFactory;
use exface\Core\CommonLogic\DataSheets\DataAggregation;
use exface\Core\Widgets\Traits\AttributeCaptionTrait;

/**
 * The DataColumn represents a column in Data-widgets a DataTable.
 *
 * DataColumns are not always visible as columns. But they are always there, when tabular data is needed
 * for a widget. A DataColumn has a caption (header), an expression for it's contents (an attribute alias,
 * a formula, etc.) and an optional footer, where the contents can be summarized (e.g. summed up).
 *
 * Many widgets support inline-editing. Their columns can be made editable by defining an cell widget
 * for the column. Any input or display widget (Inputs, Combo, Text, ProgressBar etc.) can be used as cell widget.
 *
 * DataColumns can also be made sortable. This is usefull for facade features like changing the sort
 * order via mouse click on the colum header.
 *
 * @method DataColumnGroup getParent()
 * 
 * @author Andrej Kabachnik
 *        
 */
class DataColumn extends AbstractWidget implements iShowDataColumn, iShowSingleAttribute, iCanBeAligned
{
    use iCanBeAlignedTrait {
        getAlign as getAlignDefault;
    }
    use AttributeCaptionTrait;
    
    private $attribute_alias = null;

    private $sortable = true;

    private $footer = false;
    
    private $widthMax = null;

    /**
     * 
     * @var iHaveValue
     */
    private $cellWidget = null;

    private $editable = null;
    
    private $default_sorting_direction = null;

    private $aggregate_function = null;

    private $include_in_quick_search = false;

    private $cell_styler_script = null;

    private $data_column_name = null;

    public function hasFooter()
    {
        if (! empty($this->footer))
            return true;
        else
            return false;
    }

    public function getAttributeAlias()
    {
        return $this->attribute_alias;
    }

    /**
     * Makes the column display an attribute of the Data's meta object or a related object.
     *
     * The attribute_alias can contain a relation path and/or an optional aggregator: e.g.
     * "attribute_alias": "ORDER__POSITION__VALUE:SUM"
     *
     * WARNING: This field currently also accepts formulas and strings. However, this feature
     * is not quite stable and it is not guaranteed for it to remain in future (it is more
     * likely that formulas and widget links will be moved to a new generalized property of the
     * DataColumn - presumabely "expression")
     *
     * @uxon-property attribute_alias
     * @uxon-type metamodel:attribute
     *
     * @param string $value            
     */
    public function setAttributeAlias($value)
    {
        $this->attribute_alias = $value;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isSortable()
    {
        if (is_null($this->sortable)) {
            if ($attr = $this->getAttribute()) {
                $this->sortable = $attr->isSortable();
            }
        }
        return $this->sortable;
    }

    /**
     * Set to FALSE to disable sorting data via this column.
     *
     * If the column represents a meta attribute, the sortable property of that attribute will be used.
     *
     * @uxon-property sortable
     * @uxon-type boolean
     *
     * @param
     *            boolean
     */
    public function setSortable($value)
    {
        $this->sortable = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Makes the column display summary information in the footer.
     * The value can be SUM, AVG, MIN, MAX, COUNT, COUNT_DISTINCT, LIST and LIST_DISTINCT.
     *
     * @uxon-property footer
     * @uxon-type [SUM,AVG,MIN,MAX,COUNT,COUNT_DISTINCT,LIST,LIST_DISTINCT]
     *
     * @param string $value            
     * @return DataColumn
     */
    public function setFooter($value)
    {
        $this->footer = $value;
        return $this;
    }

    /**
     * Returns the cell widget widget instance for this column
     *
     * @return iHaveValue
     */
    public function getCellWidget()
    {
        if ($this->cellWidget === null) {
            if ($this->isBoundToAttribute() === true) {
                $attr = $this->getAttribute();
                if ($this->isEditable() === true) {
                    $uxon = $attr->getDefaultEditorUxon();
                    $uxon->setProperty('attribute_alias', $this->getAttributeAlias());
                    $this->cellWidget = WidgetFactory::createFromUxon($this->getPage(), $uxon, $this, 'Input');
                } else {
                    $uxon = $attr->getDefaultDisplayUxon();
                    $uxon->setProperty('attribute_alias', $this->getAttributeAlias());
                    $this->cellWidget = WidgetFactory::createFromUxon($this->getPage(), $uxon, $this, 'Display');
                } 
            } else {
                $this->cellWidget = WidgetFactory::create($this->getPage(), 'Display', $this);
            }
            
            if ($this->cellWidget->getWidth()->isUndefined()) {
                $this->cellWidget->setWidth($this->getWidth());
            }
            
            // Some data types require special treatment within a table to make all rows comparable.
            $type = $this->cellWidget->getValueDataType();
            if ($type instanceof NumberDataType) {
                // Numbers with a variable, but limited amount of fraction digits should
                // allways have the same amount of fraction digits in a table to ensure the
                // decimal separator is at the same place in every row.
                if (is_null($type->getPrecisionMin()) && ! is_null($type->getPrecisionMax())) {
                    $type->setPrecisionMin($type->getPrecisionMax());
                } elseif (is_null($type->getPrecisionMax())) {
                    $type->setPrecisionMax(3);
                }
            }
        }
        return $this->cellWidget;
    }

    /**
     * Returns TRUE if the column is editable and FALSE otherwise.
     * 
     * A DataColumn is concidered editable if it is either made editable explicitly
     * (`editable: true`) or belongs to an editable DataColumnGroup and represents
     * an editable attribute or is not bound to an attribute at all.
     *
     * @return boolean
     */
    public function isEditable()
    {
        if ($this->editable !== null) {
            return $this->editable;
        }
        
        $groupIsEditable = $this->getDataColumnGroup()->isEditable();
        if ($groupIsEditable === true) {
            if ($this->isBoundToAttribute()) {
                return $this->getAttribute()->isEditable();
            } else {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Makes this column editable if set to TRUE.
     * 
     * In particular, this will make the default editor of an attribute be used
     * as cell widget (instead of the default display widget).
     * 
     * If not set explicitly, the editable state of the column group will be inherited.
     * 
     * Explicitly definig an active editor as the cell widget will also set the
     * column editable automatically.
     * 
     * @uxon-property editable
     * @uxon-type boolean
     * 
     * @param boolean $true_or_false
     * @return \exface\Core\Widgets\DataColumn
     */
    public function setEditable($true_or_false)
    {
        $this->editable = BooleanDataType::cast($true_or_false);
        if ($this->editable === true) {
            $this->getDataColumnGroup()->setEditable(true);
        }
        return $this;
    }

    /**
     * Defines the widget to be used in each cell of this column.
     *
     * Any value-widget can be used in a column cell (e.g. an Input or a Display).
     * Setting an active input-widget will automatically make the column `editable`.
     * Using a display-widget will, in-turn make it non-editable.
     *
     * Example for a standard display widget with an specific data type:
     * 
     * ```
     * {
     *  "attribute_alias": "MY_ATTRIBUTE",
     *  "cell_widget": {
     *      "widget_type": "Display",
     *      "value_data_type": "exface.Core.Date"
     *  }
     * }
     * 
     * ```
     * 
     * Example for a custom display widget:
     * 
     * ```
     * {
     *  "attribute_alias": "MY_ATTRIBUTE",
     *  "cell_widget": {
     *      "widget_type": "ProgressBar"
     *  }
     * }
     * 
     * ```
     *
     * Example for an editor:
     * 
     * ```
     * {
     *  "attribute_alias": "MY_ATTRIBUTE",
     *  "cell_widget": {
     *      "widget_type": "InputNumber"
     *  }
     * }
     * 
     * ```
     *
     * @uxon-property cell_widget
     * @uxon-type \exface\Core\Widgets\Value
     * @uxon-template {"widget_type": ""}
     *
     * @param UxonObject $uxon_object            
     * @return DataColumn
     */
    public function setCellWidget(UxonObject $uxon_object)
    {
        try {
            $cellWidget = WidgetFactory::createFromUxon($this->getPage(), UxonObject::fromAnything($uxon_object), $this);
            $cellWidget->setAttributeAlias($this->getAttributeAlias());
            $this->cellWidget = $cellWidget;
            if ($cellWidget instanceof iTakeInput) {
                $this->setEditable($cellWidget->isReadonly() === false);
            } elseif ($cellWidget instanceof Display) {
                $this->setEditable(false);
            }
        } catch (\Throwable $e) {
            throw new WidgetConfigurationError($this, 'Cannot set cell widget for ' . $this->getWidgetType() . '. ' . $e->getMessage() . ' See details below.', null, $e);
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Widgets\iCanBeAligned::getAlign()
     */
    public function getAlign()
    {
        if (! $this->isAlignSet()) {
            if ($this->getDataType() instanceof NumberDataType || $this->getDataType() instanceof PriceDataType || $this->getDataType() instanceof DateDataType) {
                $this->setAlign(EXF_ALIGN_OPPOSITE);
            } elseif ($this->getDataType() instanceof BooleanDataType) {
                $this->setAlign(EXF_ALIGN_CENTER);
            } else {
                $this->setAlign(EXF_ALIGN_DEFAULT);
            }
        }
        return $this->getAlignDefault();
    }

    /**
     * Returns the data type of the column. 
     * 
     * The column's data_type can either be set explicitly by UXON, or is derived from the shown meta attribute.
     * If there is neither an attribute bound to the column, nor an explicit data_type, the base data type
     * is returned.
     *
     * @return DataTypeInterface
     */
    public function getDataType()
    {
        return $this->getCellWidget()->getValueDataType();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Widgets\iShowSingleAttribute::getAttribute()
     */
    function getAttribute()
    {
        try {
            return $this->getMetaObject()->getAttribute($this->getAttributeAlias());
        } catch (MetaAttributeNotFoundError $e) {
            if ($this->getExpression()->isFormula()) {
                return $this->getMetaObject()->getAttribute($this->getExpression()->getRequiredAttributes()[0]);
            }
            throw new WidgetPropertyInvalidValueError($this, 'Attribute "' . $this->getAttributeAlias() . '" specified for widget ' . $this->getWidgetType() . ' not found for the widget\'s object "' . $this->getMetaObject()->getAliasWithNamespace() . '"!', null, $e);
        }
    }

    public function getAggregator() : ?AggregatorInterface
    {
        if ($this->aggregate_function === null) {
            if ($aggr = DataAggregation::getAggregatorFromAlias($this->getWorkbench(), $this->getAttributeAlias())) {
                $this->setAggregator($aggr);
            }
        }
        return $this->aggregate_function;
    }
    
    public function hasAggregator() : bool
    {
        return $this->getAggregator() !== null;
    }

    /**
     * 
     * @param AggregatorInterface|string $aggregator_or_string
     * @return \exface\Core\Widgets\DataColumn
     */
    public function setAggregator($aggregator_or_string)
    {
        if ($aggregator_or_string instanceof AggregatorInterface){
            $aggregator = $aggregator_or_string;
        } else {
            $aggregator = new Aggregator($this->getWorkbench(), $aggregator_or_string);
        }
        $this->aggregate_function = $aggregator;
        return $this;
    }

    public function getIncludeInQuickSearch()
    {
        return $this->include_in_quick_search;
    }

    /**
     * Set to TRUE to make the quick-search include this column (if the widget support quick search).
     *
     * @uxon-property include_in_quick_search
     * @uxon-type boolean
     *
     * @param boolean $value            
     * @return DataColumn
     */
    public function setIncludeInQuickSearch($value)
    {
        $this->include_in_quick_search = \exface\Core\DataTypes\BooleanDataType::cast($value);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Widgets\AbstractWidget::getChildren()
     */
    public function getChildren() : \Iterator
    {
        yield $this->getCellWidget();
    }

    /**
     *
     * @return string
     */
    public function getCellStylerScript()
    {
        return $this->cell_styler_script;
    }

    /**
     * Specifies a facade-specific script to style the column: e.g.
     * JavaScript for HTML-facades.
     *
     * The exact effect of the cell_styler_script depends solemly on the implementation of the widget
     * in the specific facade.
     *
     * @uxon-property cell_styler_script
     * @uxon-type string
     *
     * @param string $value            
     * @return \exface\Core\Widgets\DataColumn
     */
    public function setCellStylerScript($value)
    {
        $this->cell_styler_script = $value;
        return $this;
    }

    /**
     *
     * @return ExpressionInterface
     */
    public function getExpression()
    {
        $exface = $this->getWorkbench();
        return ExpressionFactory::createFromString($exface, $this->getAttributeAlias());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Widgets\iShowDataColumn::getDataColumnName()
     */
    public function getDataColumnName()
    {
        if (is_null($this->data_column_name)) {
            $this->data_column_name = \exface\Core\CommonLogic\DataSheets\DataColumn::sanitizeColumnName($this->getAttributeAlias());
        }
        return $this->data_column_name;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Widgets\iShowDataColumn::setDataColumnName()
     */
    public function setDataColumnName($value)
    {
        $this->data_column_name = $value;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Widgets\AbstractWidget::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = parent::exportUxonObject();
        // TODO add properties specific to this widget here
        return $uxon;
    }
    
    /**
     * 
     * @return \exface\Core\DataTypes\SortingDirectionsDataType
     */
    public function getDefaultSortingDirection()
    {
        if(is_null($this->default_sorting_direction)){
            return $this->getDataType()->getDefaultSortingDirection();
        }
        return $this->default_sorting_direction;
    }
    
    /**
     * Defines the default sorting direction for this column: ASC or DESC.
     * 
     * The default direction is used if sorting the column without a
     * direction being explicitly specified: e.g. when clicking on a
     * sortable table header.
     * 
     * If not set, the default sorting direction of the attribute will
     * be used for columns representing attributes or the default sorting
     * direction of the data type of the columns expression.
     * 
     * @uxon-property default_sorting_direction
     * @uxon-type [ASC,DESC]
     * 
     * @param SortingDirectionsDataType|string $asc_or_desc
     */
    public function setDefaultSortingDirection($asc_or_desc)
    {
        if ($asc_or_desc instanceof SortingDirectionsDataType){
            // Everything OK. Just proceed
        } elseif (SortingDirectionsDataType::isValidValue(strtoupper($asc_or_desc))){
            $asc_or_desc = DataTypeFactory::createFromPrototype($this->getWorkbench(), SortingDirectionsDataType::class)->withValue(strtoupper($asc_or_desc));
        } else {
            throw new WidgetPropertyInvalidValueError($this, 'Invalid value "' . $asc_or_desc . '" for default sorting direction in data column: use ASC or DESC');
        }
        $this->default_sorting_direction = $asc_or_desc;
        return $this;
    }

    /**
     * Returns TRUE if this widget references a meta attribute and FALSE otherwise.
     *
     * @return boolean
     */
    public function isBoundToAttribute()
    {
        return $this->getAttributeAlias() !== null && $this->getAttributeAlias() !== '' ? true : false;
    }
    
    /**
     * 
     * @return \exface\Core\Widgets\DataColumnGroup
     */
    public function getDataColumnGroup()
    {
        return $this->getParent();
    }
    
    /**
     * 
     * @return \exface\Core\Widgets\Data
     */
    public function getDataWidget()
    {
        return $this->getParent()->getDataWidget();
    }
    
    /**
     * 
     * @return WidgetDimension
     */
    public function getWidthMax() : WidgetDimension
    {
        if ($this->widthMax === null) {
            $this->widthMax = WidgetDimensionFactory::createEmpty($this->getWorkbench());
        }
        return $this->widthMax;
    }
    
    /**
     * Sets the maximum width for a column.
     * 
     * This property takes the same values as "width" or "height", but unlike "width" it
     * will allow the column to be smaller, but never wider, than the given value. "Width"
     * on the other hand, will make the column have a fixed width.
     * 
     * @uxon-property width_max
     * @uxon-type string
     * 
     * @param string|WidgetDimension $stringOrDimension
     * @return DataColumn
     */
    public function setWidthMax($stringOrDimension) : DataColumn
    {
        $this->widthMax = WidgetDimensionFactory::createFromAnything($this->getWorkbench(), $stringOrDimension);
        return $this;
    }
}
?>