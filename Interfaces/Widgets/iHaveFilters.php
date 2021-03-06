<?php
namespace exface\Core\Interfaces\Widgets;

use exface\Core\Widgets\Filter;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\WidgetInterface;

interface iHaveFilters extends WidgetInterface
{

    public function addFilter(\exface\Core\Widgets\AbstractWidget $filter_widget);

    public function getFilters();
    
    public function getFilter($filter_widget_id);
    
    /**
     * Returns all filters, that have values and thus will be applied to the result
     *
     * @return Filter[]
     */
    public function getFiltersApplied();

    /**
     * @param UxonObject[] $uxon_objects
     * @return iHaveFilters
     */
    public function setFilters(UxonObject $uxon_objects);
}