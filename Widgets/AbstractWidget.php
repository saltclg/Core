<?php namespace exface\Core\Widgets;

use exface\Core\CommonLogic\Model\Expression;
use exface\Core\Interfaces\Widgets\iHaveChildren;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Exceptions\UiWidgetException;
use exface\Core\Interfaces\Widgets\iTriggerAction;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;
use exface\Core\CommonLogic\WidgetLink;
use exface\Core\Exceptions\UxonParserError;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\CommonLogic\NameResolver;
use exface\Core\CommonLogic\Model\Object;
use exface\Core\Factories\WidgetDimensionFactory;
use exface\Core\Interfaces\UiPageInterface;
use exface\Core\Exceptions\UiWidgetInvalidIdError;
use exface\Core\CommonLogic\Model\RelationPath;
use exface\Core\Factories\RelationPathFactory;

/**
 * Basic ExFace widget
 * @author Andrej Kabachnik
 *
 */
abstract class AbstractWidget implements WidgetInterface, iHaveChildren {
	private $id_specified = null;
	private $id_autogenerated = null;
	private $caption = null;
	private $hint = '';
	private $widget_type = null;
	private $meta_object_id = null;
	private $object_alias = null;
	private $object_relation_path_to_parent = null;
	private $object_relation_path_from_parent = null;
	private $object_qualified_alias = null;
	private $value = null;
	private $disabled = NULL;
	private $width = null;
	private $height = null;
	private $hidden = false;
	private $visibility = null;
	/** @var \exface\Core\Widgets\AbstractWidget the parent widget */
	private $parent = null;
	private $ui = null;
	private $id_specified_by_user = false;
	private $data_connection_alias_specified_by_user = NULL;
	private $prefill_data = null;
	private $uxon = null;
	private $hide_caption = false;
	private $page = null;
	
	/**
	 * @deprecated use WidgetFactory::create() instead!
	 * @param UiPageInterface $page
	 * @param WidgetInterface $parent_widget
	 * @param string $fixed_widget_id
	 */
	function __construct(UiPageInterface &$page, WidgetInterface $parent_widget = null, $fixed_widget_id = null){
		$this->page = $page;
		$this->widget_type = substr(get_class($this), (strrpos(get_class($this), '\\')+1));
		// Set the parent widget if known
		if ($parent_widget) {
			$this->set_parent($parent_widget);
		}
		
		if ($fixed_widget_id){
			$this->set_id_specified($fixed_widget_id);
		}
		
		// Add widget to the page. It will now get an autogenerated id
		$page->add_widget($this);
		$this->init();
	}
	
	/**
	 * This method is called every time a widget is instantiated an can be used as a hook for additional initializing logics.
	 * @return void
	 */
	protected function init(){
		
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::import_uxon_object()
	 */
	function import_uxon_object(\stdClass $source){
		$vars = get_object_vars($source);
		foreach ($vars as $var => $val){
			if (method_exists($this, 'set_'.$var)){
				call_user_func(array($this, 'set_'.$var), $val);
			} else {
				throw new UxonParserError('Property "' . $var . '" of widget "' . $this->get_widget_type() . '" cannot be set: setter function not found!');
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::prefill()
	 */
	function prefill(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet){
		$this->set_prefill_data($data_sheet);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::prepare_data_sheet_to_read()
	 */
	public function prepare_data_sheet_to_read(DataSheetInterface $data_sheet = null){
		if (is_null($data_sheet)){
			$data_sheet = $this->create_data_sheet();
		}
		return $data_sheet;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::prepare_data_sheet_to_prefill()
	 */
	public function prepare_data_sheet_to_prefill(DataSheetInterface $data_sheet = null){
		if (is_null($data_sheet)){
			$data_sheet = $this->create_data_sheet();
		}
		return $data_sheet;
	}
	
	protected function create_data_sheet(){
		return $this->get_workbench()->data()->create_data_sheet($this->get_meta_object());
	}
	
	/**
	 * Sets the widget type. Set to the name of the widget, to instantiate it (e.g. "DataTable").
	 * 
	 * @uxon-property widget_type
	 * @uxon-type string 
	 * 
	 * @param string $value
	 */
	protected function set_widget_type($value){
		if ($value) $this->widget_type = $value;
	}
	
	/**
	 * Sets the caption or title of the widget.
	 * 
	 * @uxon-property caption
	 * @uxon-type string
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_caption()
	 */
	function set_caption($caption){
		$this->caption = $caption;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_meta_object_id()
	 */
	function get_meta_object_id(){
		if (!$this->meta_object_id) return $this->get_meta_object()->get_id();
		return $this->meta_object_id;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_meta_object_id()
	 */
	function set_meta_object_id($id){
		$this->meta_object_id = $id;
	}
	
	/**
	 * Explicitly specifies the ID of the widget. The ID must be unique on every page containing the widget and can be used in widget links
	 * 
	 * @uxon-property id
	 * @uxon-type string
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_id()
	 */
	function set_id($id){
		return $this->set_id_specified($id);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::is_container()
	 */
	function is_container(){
		if ($this instanceof iHaveChildren) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_children()
	 */
	public function get_children(){
		return array();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_children_recursive()
	 */
	public function get_children_recursive(){
		$children = $this->get_children();
		foreach ($children as $child){
			$children = array_merge($children, $child->get_children_recursive());
		}
		return $children;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::find_child_recursive()
	 */
	function find_child_recursive($widget_id){
		foreach ($this->get_children() as $child){
			if ($child->get_id() == $widget_id) {
				return $child;
			} elseif ($found = $child->find_child_recursive($widget_id)) {
				return $found;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_caption()
	 */
	function get_caption(){
		return $this->caption;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_hide_caption()
	 */
	public function get_hide_caption() {
		return $this->hide_caption;
	}
	
	/**
	 * Set to TRUE to hide the caption of the widget. FALSE by default.
	 * 
	 * @uxon-property hide caption
	 * @uxon-type boolean 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_hide_caption()
	 */
	public function set_hide_caption($value) {
		$this->hide_caption = $value;
		return $this;
	}  
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_meta_object()
	 */
	function get_meta_object() {
		if ($this->meta_object_id) {
			$obj = $this->get_ui()->get_workbench()->model()->get_object($this->meta_object_id);
		} elseif ($this->get_object_qualified_alias()) {
			$obj = $this->get_ui()->get_workbench()->model()->get_object($this->get_object_qualified_alias());
		} elseif ($this->get_parent()) {
			$obj = $this->get_parent()->get_meta_object();
		} else {
			throw new \exface\Core\Exceptions\UiWidgetException('A widget must have either an object_id, an object_alias or a parent widget with an object reference!');
		}
		$this->set_meta_object_id($obj->get_id());
		return $obj;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_meta_object()
	 */
	function set_meta_object(Object $object){
		return $this->set_meta_object_id($object->get_id());
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_id()
	 */
	function get_id(){
		if ($id = $this->get_id_specified()){
			return $id;
		}
		return $this->get_id_autogenerated();
	}
	
	public function get_id_specified() {
		return $this->id_specified;
	}
	
	public function set_id_specified($value) {
		// Don't do anything, if the id's are identical
		if ($this->get_id() === $value){
			return $this;
		}
		
		// Just set the id_specified property if there is no id at all at this point
		if (!$this->get_id()){
			$this->id_specified = $value;
			return $this;
		}
		
		$old_id = $this->id_specified;
		
		try {
			$this->id_specified = $value;
			$this->get_page()->add_widget($this);
			if ($old_id){
				$this->get_page()->remove_widget($this->id_specified);
			}
		} catch (UiWidgetInvalidIdError $e){
			$this->id_specified = $old_id;
			$e->rethrow();
		}
		
		return $this;
	}
	
	public function get_id_autogenerated() {
		return $this->id_autogenerated;
	}
	
	public function set_id_autogenerated($value) {
		$this->id_autogenerated = $value;
		return $this;
	}    
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_widget_type()
	 */
	function get_widget_type(){
		return $this->widget_type;
	}
	
	/**
	 * TODO Move to iHaveValue-Widgets or trait
	 * @return string|NULL
	 */
	public function get_value() {
		if ($this->get_value_expression()){
			return $this->get_value_expression()->to_string();
		} 
		return null;
	}
	
	/**
	 * TODO Move to iHaveValue-Widgets or trait
	 * @return Expression
	 */
	public function get_value_expression(){
		return $this->value;
	}
	
	/**
	 * Explicitly sets the value of the widget
	 * 
	 * @uxon-property value
	 * @uxon-type Expression|string 
	 * 
	 * TODO Move to iHaveValue-Widgets or trait
	 * @param Expression|string $expression_or_string
	 */
	public function set_value($expression_or_string) {
		if ($expression_or_string instanceof expression){
			$this->value = $expression_or_string;
		} else {
			$this->value = $this->get_workbench()->model()->parse_expression($expression_or_string, $this->get_meta_object());
		}
		return $this;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::is_disabled()
	 */
	public function is_disabled() {
		return $this->disabled;
	}
	
	/**
	 * Set to TRUE to disable the widget. Disabled widgets cannot accept input or interact with the user in any other way.
	 * 
	 * @uxon-property disabled
	 * @uxon-type boolean
	 * 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_disabled()
	 */
	public function set_disabled($value) {
		$this->disabled = $value;
		return $this;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_width()
	 */
	public function get_width() {
		if (!$this->width){
			$exface = $this->get_workbench();
			$this->width = WidgetDimensionFactory::create_empty($exface);
		}
		return $this->width;
	}
	
	/**
	 * Sets the width of the widget. Set to "1" for default widget width in a template or "max" for maximum width possible.
	 * 
	 * The width can be specified either in 
	 * - template-specific relative units (e.g. "width: 2" makes the widget twice as wide
	 * as the default width of a widget in the current template) 
	 * - percent (e.g. "width: 50%" will make the widget take up half the available space)
	 * - any other template-compatible units (e.g. "width: 200px" will work in CSS-based templates)
	 * 
	 * @uxon-property width
	 * @uxon-type string
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_width()
	 */
	public function set_width($value) {
		$exface = $this->get_workbench();
		$this->width = WidgetDimensionFactory::create_from_anything($exface, $value);
		return $this;
	} 

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_height()
	 */
	public function get_height() {
		if (!$this->height){
			$exface = $this->get_workbench();
			$this->height = WidgetDimensionFactory::create_empty($exface);
		}
		return $this->height;
	}
	
	/**
	 * Sets the height of the widget. Set to "1" for default widget height in a template or "max" for maximum height possible.
	 * 
	 * The height can be specified either in 
	 * - template-specific relative units (e.g. "height: 2" makes the widget twice as high
	 * as the default width of a widget in the current template) 
	 * - percent (e.g. "height: 50%" will make the widget take up half the available space)
	 * - any other template-compatible units (e.g. "height: 200px" will work in CSS-based templates)
	 * 
	 * @uxon-property height
	 * @uxon-type string
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_height()
	 */
	public function set_height($value) {
		$exface = $this->get_workbench();
		$this->height = WidgetDimensionFactory::create_from_anything($exface, $value);
		return $this;
	}
	
	/**
	 * Returns the full alias of the main meta object (prefixed by the app namespace - e.g. CRM.CUSTOMER)
	 */
	public function get_object_qualified_alias() {
		return $this->object_qualified_alias;
	}
	
	/**
	 * Sets the alias of the main object of the widget. Use qualified aliases (with namespace)!
	 * 
	 * @uxon-property object_alias
	 * @uxon-type string
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_object_alias()
	 */
	public function set_object_alias($full_or_object_alias) {
		if ($app = $this->get_ui()->get_workbench()->model()->get_namespace_from_qualified_alias($full_or_object_alias)){
			$this->object_qualified_alias = $full_or_object_alias;
			$this->object_alias = $this->get_ui()->get_workbench()->model()->get_object_alias_from_qualified_alias($full_or_object_alias);
		} else {
			if ($this->get_parent()){
				$app = $this->get_parent()->get_meta_object()->get_namespace();
			}
			$this->object_alias = $full_or_object_alias;
			$this->object_qualified_alias = $app . NameResolver::NAMESPACE_SEPARATOR . $this->object_alias;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_object_relation_path_from_parent()
	 */
	public function get_object_relation_path_from_parent() {
		if (is_null($this->object_relation_path_from_parent)){
			// If there is no relation to the parent set yet, see if there is a parent.
			// If not, do not do anything - maybe there will be some parent when the method is called the next time
			if ($this->get_parent()){
				// If there is no relation path yet, create one
				$this->object_relation_path_from_parent = RelationPathFactory::create_for_object($this->get_parent()->get_meta_object());
				// If the parent is based on another object, search for a relation to it - append it to the path if found
				if (!$this->get_parent()->get_meta_object()->is($this->get_meta_object())){
					if ($this->object_relation_path_to_parent){
						// If we already know the path from this widgets object to the parent, just reverse it
						$this->object_relation_path_from_parent = $this->get_object_relation_path_to_parent()->reverse();
					} elseif ($rel = $this->get_parent()->get_meta_object()->find_relation($this->get_meta_object_id(), true)){
						// Otherwise, try to find a path automatically 
						$this->object_relation_path_from_parent->append_relation($rel);
					}
				}
			}
		} elseif (!($this->object_relation_path_from_parent instanceof RelationPath)){
			$this->object_relation_path_from_parent = RelationPathFactory::create_from_string($this->get_parent()->get_meta_object(), $this->object_relation_path_from_parent);
		} else {
			// If there is a relation path already built, check if it still fits to the current parent widget (which might have changed)
			// If not, removed the cached path and runt the getter again to try to find a new path
			if (!$this->get_parent()->get_meta_object()->is($this->object_relation_path_from_parent->get_start_object())){
				$this->object_relation_path_from_parent = null;
				return $this->get_object_relation_path_from_parent();
			}
		}
		return $this->object_relation_path_from_parent;
	}
	
	/**
	 * Sets the relation path from the parent widget's object to this widget's object
	 * 
	 * @uxon-property object_relation_path_from_parent
	 * @uxon-type string 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_object_relation_path_from_parent()
	 */
	public function set_object_relation_path_from_parent($string) {
		$this->object_relation_path_from_parent = $string;
		if ($this->is_object_inherited_from_parent()){
			$this->set_object_alias($this->get_parent()->get_meta_object()->get_related_object($string)->get_alias_with_namespace());
		}
		return $this;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::is_object_inherited_from_parent()
	 */
	public function is_object_inherited_from_parent(){
		if (is_null($this->object_qualified_alias) && is_null($this->meta_object_id)){
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_object_relation_path_to_parent()
	 */
	public function get_object_relation_path_to_parent() {
		if (is_null($this->object_relation_path_to_parent)){
			// If there is no relation to the parent set yet, see if there is a parent. 
			// If not, do not do anything - maybe there will be some parent when the method is called the next time
			if ($this->get_parent()){
				// If there is no relation path yet, create one
				$this->object_relation_path_to_parent = RelationPathFactory::create_for_object($this->get_meta_object());
				// If the parent is based on another object, search for a relation to it - append it to the path if found
				if (!$this->get_parent()->get_meta_object()->is($this->get_meta_object())){
					if ($this->object_relation_path_from_parent){
						// If we already know the path from the parents object to this widget, just reverse it
						$this->object_relation_path_to_parent = $this->get_object_relation_path_to_parent()->reverse();
					} elseif ($rel = $this->get_meta_object()->find_relation($this->get_parent()->get_meta_object_id(), true)){
						$this->object_relation_path_to_parent->append_relation($rel);
					}
				}
			}
		} elseif (!($this->object_relation_path_to_parent instanceof RelationPath)){
			// If there is a path, but it is a string (e.g. it was just set via UXON import), create an object from it
			$this->object_relation_path_to_parent = RelationPathFactory::create_from_string($this->get_meta_object(), $this->object_relation_path_to_parent);
		} else {
			// If there is a relation path already built, check if it still fits to the current parent widget (which might have changed)
			// If not, removed the cached path and runt the getter again to try to find a new path
			if (!$this->get_parent()->get_meta_object()->is($this->object_relation_path_to_parent->get_end_object())){
				$this->object_relation_path_to_parent = null;
				return $this->get_object_relation_path_to_parent();
			}
		}
		return $this->object_relation_path_to_parent;
	}
	
	/**
	 * Sets the relation path from this widget's meta object to the object of the parent widget
	 * 
	 * @uxon-property object_relation_path_to_parent
	 * @uxon-type string 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_object_relation_path_to_parent()
	 */
	public function set_object_relation_path_to_parent($string) {
		$this->object_relation_path_to_parent = $string;
		return $this;
	}
 
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_page_id()
	 */
	public function get_page_id() {
		return $this->get_page()->get_id();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_parent()
	 */
	public function get_parent() {
		return $this->parent;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_parent()
	 */
	public function set_parent(WidgetInterface &$widget) {
		$this->parent = $widget;
	}  
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_ui()
	 */
	public function get_ui() {
		return $this->get_page()->get_workbench()->ui();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_hint()
	 */
	public function get_hint() {
		if (!$this->hint && ($this instanceof iShowSingleAttribute) && $this->get_attribute()){
			$this->set_hint($this->get_attribute()->get_hint());
		}
		return $this->hint;
	}
	
	/**
	 * Sets a hint message for the widget. The hint will typically be used for pop-overs, etc.
	 * 
	 * @uxon-property hint
	 * @uxon-type string 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_hint()
	 */
	public function set_hint($value) {
		$this->hint = $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::is_hidden()
	 */
	public function is_hidden() {
		return $this->hidden;
	}
	
	/**
	 * Set to TRUE to hide the widget. The same effect can be achieved by setting "visibility: hidden"
	 * 
	 * @uxon-property hidden
	 * @uxon-type boolean 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_hidden()
	 */
	public function set_hidden($value) {
		$this->hidden = $value;
		if ($value == true && $this->get_visibility() != EXF_WIDGET_VISIBILITY_HIDDEN){
			$this->set_visibility(EXF_WIDGET_VISIBILITY_HIDDEN);
		}
	}	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_visibility()
	 */
	public function get_visibility() {
		if ($this->visibility === null) $this->set_visibility(EXF_WIDGET_VISIBILITY_NORMAL);
		return $this->visibility;
	}
	
	/**
	 * Sets the visibility of the widget: normal, hidden, optional, promoted.
	 * 
	 * @uxon-property visibility
	 * @uxon-type string 
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_visibility()
	 */
	public function set_visibility($value) {
		if ($value != EXF_WIDGET_VISIBILITY_HIDDEN
		&& $value != EXF_WIDGET_VISIBILITY_NORMAL
		&& $value != EXF_WIDGET_VISIBILITY_OPTIONAL
		&& $value != EXF_WIDGET_VISIBILITY_PROMOTED){
			throw new \exface\Core\Exceptions\UiWidgetConfigException('Invalid visibility value "' . $value . '" for widget "' . $this->get_widget_type() . '"!');
			return;
		}
		$this->visibility = $value;
		
		if ($value == EXF_WIDGET_VISIBILITY_HIDDEN && !$this->is_hidden()){
			$this->set_hidden(true);
		} else {
			$this->set_hidden(false);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_prefill_data()
	 */
	public function get_prefill_data() {
		return $this->prefill_data;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_prefill_data()
	 */
	public function set_prefill_data(DataSheetInterface $data_sheet) {
		$this->prefill_data = $data_sheet;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::implements_interface()
	 */
	public function implements_interface($interface_name){
		$type_class = '\\exface\\Core\\Interfaces\\Widgets\\' . $interface_name;
		if ($this instanceof $type_class){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::is_of_type()
	 */
	public function is_of_type($type){
		$type_class = '\\exface\\Core\\Widgets\\' . $type;
		if ($this instanceof $type_class){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_actions()
	 */
	public function get_actions($qualified_action_alias = null, $action_id = null){
		$actions = array();
		foreach ($this->get_children() as $child){
			// If the child triggers an action itself, check if the action fits the filters an add it to the array
			if ($child instanceof iTriggerAction){
				if (($qualified_action_alias && $child->get_action()->get_alias_with_namespace() == $qualified_action_alias)
				|| ($action_id && $child->get_action()->get_id() == $action_id)
				|| (!$qualified_action_alias && !$action_id)){
					$actions[] = $child->get_action();
				}
			}
			
			// If the child has children itself, call the method recursively
			$actions = array_merge($actions, $child->get_actions($qualified_action_alias, $action_id));
		}
		return $actions;
	}	

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_aggregations()
	 */
	public function get_aggregations(){
		return array();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::set_data_connection_alias()
	 */
	public function set_data_connection_alias($value) {
		$this->data_connection_alias_specified_by_user = $value;
		$this->get_meta_object()->set_data_connection_alias($value);
		return $this;
	}  
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::create_widget_link()
	 */
	public function create_widget_link(){
		$exface = $this->get_workbench();
		$link = new WidgetLink($exface);
		$link->set_widget_id($this->get_id());
		$link->set_page_id($this->get_page_id());
		return $link;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\ExfaceClassInterface::exface()
	 */
	public function get_workbench(){
		return $this->get_page()->get_workbench();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\WidgetInterface::get_page()
	 */
	public function get_page(){
		return $this->page;
	}
	  
}
?>