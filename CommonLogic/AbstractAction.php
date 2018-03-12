<?php
namespace exface\Core\CommonLogic;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\Model\MetaObjectInterface;
use exface\Core\Interfaces\Actions\iCanBeUndone;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\ActionFactory;
use exface\Core\Factories\EventFactory;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Factories\WidgetLinkFactory;
use exface\Core\Exceptions\Model\MetaObjectNotFoundError;
use exface\Core\Exceptions\Actions\ActionObjectNotSpecifiedError;
use exface\Core\Interfaces\DataSources\DataTransactionInterface;
use exface\Core\DataTypes\StringDataType;
use exface\Core\CommonLogic\Traits\ImportUxonObjectTrait;
use exface\Core\Exceptions\UnexpectedValueException;
use exface\Core\Interfaces\AppInterface;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;
use exface\Core\Factories\DataSheetMapperFactory;
use exface\Core\Interfaces\Widgets\iUseInputWidget;
use exface\Core\CommonLogic\Selectors\ActionSelector;
use exface\Core\Interfaces\Selectors\AliasSelectorInterface;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\Interfaces\Tasks\TaskResultInterface;
use exface\Core\Interfaces\iCanBeConvertedToUxon;
use exface\Core\Interfaces\Actions\iModifyData;

/**
 * The abstract action is a generic implementation of the ActionInterface, that simplifies 
 * the creation of custom actions.
 * 
 * To implement a specific action one atually only needs to implement the abstract perform() 
 * method. All core actions are made like this.
 *
 * The abstract action dispatches the following events prefixed by the actions alias (@see ActionEvent):
 * - Perform (.Before/.After)
 *
 * @author Andrej Kabachnik
 *        
 */
abstract class AbstractAction implements ActionInterface
{
    use ImportUxonObjectTrait {
		importUxonObject as importUxonObjectDefault;
	}

    private $id = null;

    private $alias = null;

    private $name = null;

    private $exface = null;

    private $app = null;

    /** @var WidgetInterface widget, that called this action */
    private $trigger_widget = null;

    private $result_message_text = null;

    private $is_undoable = null;

    /**
     * @var DataSheetInterface
     */
    private $input_data_preset = null;
    
    private $input_mappers = [];

    /**
     * @var string
     */
    private $icon = null;

    /**
     * @var integer
     */
    private $input_rows_min = 0;

    /**
     * @var integer
     */
    private $input_rows_max = null;

    /**
     * @var array
     */
    private $disabled_behaviors = array();

    /**
     * @var string
     */
    private $meta_object = null;

    private $autocommit = true;

    /**
     *
     * @deprecated use ActionFactory instead
     * @param AppInterface $app            
     * @param WidgetInterface $trigger_widget            
     */
    public function __construct(AppInterface $app, WidgetInterface $trigger_widget = null)
    {
        $this->app = $app;
        $this->exface = $app->getWorkbench();
        if ($trigger_widget) {
            $this->setWidgetDefinedIn($trigger_widget);
        }
        $this->init();
    }

    protected function init()
    {}

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\AliasInterface::getAlias()
     */
    public function getAlias()
    {
        if (is_null($this->alias)) {
            $class = explode('\\', get_class($this));
            $this->alias = end($class);
        }
        return $this->alias;
    }

    public function setAlias($value)
    {
        $this->alias = $value;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\AliasInterface::getAliasWithNamespace()
     */
    public function getAliasWithNamespace()
    {
        return $this->getNamespace() . AliasSelectorInterface::ALIAS_NAMESPACE_DELIMITER . $this->getAlias();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\AliasInterface::getNamespace()
     */
    public function getNamespace()
    {
        return $this->getApp()->getAliasWithNamespace();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getId()
     */
    public function getId()
    {
        if (is_null($this->id)) {
            $this->id = md5($this->exportUxonObject()->toJson());
        }
        return $this->id;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getApp()
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Loads data from a standard UXON object into any action using setter functions.
     * E.g. calls $this->setId($source->id) for every property of the source object. Thus the behaviour of this
     * function like error handling, input checks, etc. can easily be customized by programming good
     * setters.
     *
     * @param UxonObject $source            
     */
    public function importUxonObject(UxonObject $uxon)
    {
        // Skip alias property if found because it was processed already to instantiate the right action class.
        // Setting the alias after instantiation is currently not possible beacuase it would mean recreating
        // the entire action.
        return $this->importUxonObjectDefault($uxon, array(
            'alias'
        ));
    }

    public function hasProperty($name)
    {
        return method_exists($this, 'set' . StringDataType::convertCaseUnderscoreToPascal($name));
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getIcon()
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon to be used for this action.
     * 
     * This icon will be used on buttons and menu items with this action unless they have
     * their own icons defined.
     * 
     * By default all icons from font awsome (http://fontawesome.io/icons/) are supported.
     *
     * @uxon-property icon
     * @uxon-type string
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setIcon()
     */
    public function setIcon($value)
    {
        $this->icon = $value;
    }

    /**
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getWidgetDefinedIn()
     */
    public function getWidgetDefinedIn() : WidgetInterface
    {
        return $this->trigger_widget;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setWidgetDefinedIn()
     */
    public function setWidgetDefinedIn($widget_or_widget_link)
    {
        if ($widget_or_widget_link instanceof WidgetInterface) {
            $this->trigger_widget = $widget_or_widget_link;
        } elseif ($widget_or_widget_link instanceof WidgetLink) {
            $this->trigger_widget = $widget_or_widget_link->getWidget();
        } else {
            $link = WidgetLinkFactory::createFromAnything($this->exface, $widget_or_widget_link);
            $this->trigger_widget = $link->getWidget();
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * This method actually only takes care of the infrastructure (events, etc.) while actual logic 
     * of the action sits in the perform() method that, on the other hand should not be called
     * from external sources because the developer of a specific action might not have taken care
     * of contexts, events etc. This is why handle() is final.
     * 
     * @see \exface\Core\Interfaces\Actions\ActionInterface::handle()
     */
    public final function handle(TaskInterface $task, DataTransactionInterface $transaction = null) : TaskResultInterface
    {
        $this->dispatchEvent('Perform.Before');
        
        // Start a new transaction if none passed
        if (is_null($transaction)) {
            $transaction = $this->getWorkbench()->data()->startTransaction();
        }
        
        // Call the action's logic
        $result = $this->perform($task, $transaction);
        
        $this->dispatchEvent('Perform.After');
        
        // Register the action in the action context of the window. Since it is passed by reference, we can
        // safely do it here, befor perform(). On the other hand, this gives all kinds of action event handlers
        // the possibility to access the current action and it's current state
        // FIXME re-enable action context: maybe make it work with events?
        // $this->getApp()->getWorkbench()->context()->getScopeWindow()->getActionContext()->addAction($this);
        
        // Commit the transaction if autocommit is on and the action COULD have modified data
        // We cannot rely on $result->isDataModified() at this point as it is not allways possible to determine
        // it within the action (some data source simply do give relieable feedback).
        if ($this->getAutocommit() && ($this instanceof iModifyData)) {
            $transaction->commit();
        }
        
        return $result;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getResultMessageText()
     */
    public function getResultMessageText()
    {
        return $this->result_message_text;
    }

    /**
     * Overrides the auto-generated result message with the given text.
     * The text can contain placeholders.
     *
     * Placeholders can be used for any column in the result data sheet of this action: e.g. for a CreateObject action
     * a the follwoing text could be used: "Object [#LABEL#] with id [#UID#] created". If the result sheet contains
     * multiple rows, the message text will be repeated for every row with the placeholders being replaced from that
     * row.
     *
     * @uxon-property result_message_text
     * @uxon-type string
     *
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setResultMessageText()
     */
    public function setResultMessageText($value)
    {
        $this->result_message_text = $value;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputDataPreset()
     */
    public function setInputDataPreset(DataSheetInterface $data_sheet) : ActionInterface
    {
        $this->input_data_preset = $data_sheet;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getInputDataPreset()
     */
    public function getInputDataPreset() : DataSheetInterface
    {        
        return $this->input_data_preset;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::hasInputDataPreset()
     */
    public function hasInputDataPreset() : bool
    {
        return is_null($this->input_data_preset) ? false : true;
    }
    
    /**
     * Sets preset input data for the action.
     * 
     * The preset will be merged with the task input data when the action is performed
     * or used as input data if the task will not provide any data.
     * 
     * @uxon-property input_data_sheet
     * @uxon-type \exface\Core\CommonLogic\DataSheets\DataSheet
     * 
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputDataSheet($uxon)
     */
    public function setInputDataSheet(UxonObject $uxon) : ActionInterface
    {
        return $this->setInputDataPreset(DataSheetFactory::createFromUxon($this->getWorkbench(), $uxon, $this->getMetaObject()));
    }

    /**
     * Performs the action.
     * Should be implemented in every action. Does not actually return anything, instead the result_data_sheet,
     * the result message and (if needed) a separate result object should be set within the specific implementation via
     * set_result_data_sheet(), set_result_message() and set_result() respectively!
     *
     * This method is protected because only get_result...() methods are intended to be used by external objects. In addition to performing
     * the action they also take care of saving it to the current context, etc., while perform() ist totally depending on the specific
     * action implementation and holds only the actual logic without all the overhead.
     *
     * @return void
     */
    protected abstract function perform(TaskInterface $task, DataTransactionInterface $transaction) : TaskResultInterface;

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getInputRowsMin()
     */
    public function getInputRowsMin()
    {
        return $this->input_rows_min;
    }

    /**
     * Sets the minimum number of rows the input data sheet must have for this action.
     *
     * @uxon-property input_rows_min
     * @uxon-type integer
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputRowsMin()
     */
    public function setInputRowsMin($value)
    {
        $this->input_rows_min = $value;
    }

    /**
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getInputRowsMax()
     */
    public function getInputRowsMax()
    {
        return $this->input_rows_max;
    }

    /**
     * Sets the maximum number of rows the input data sheet must have for this action.
     *
     * @uxon-property input_rows_max
     * @uxon-type integer
     * 
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputRowsMax()
     */
    public function setInputRowsMax($value)
    {
        $this->input_rows_max = $value;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getMetaObject()
     */
    public function getMetaObject()
    {
        if (is_null($this->meta_object)) {
            if ($this->hasInputDataPreset()) {
                $this->meta_object = $this->getInputDataPreset()->getMetaObject();
            } elseif ($this->isDefinedInWidget()) {
                $this->meta_object = $this->getWidgetDefinedIn()->getMetaObject();
            } else {
                throw new ActionObjectNotSpecifiedError($this, 'Cannot determine the meta object, the action is performed upon! An action must either have an input data sheet or a reference to the widget, that called it, or an explicitly specified object_alias option to determine the meta object.');
            }
        }
        return $this->meta_object;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setMetaObject()
     */
    public function setMetaObject(MetaObjectInterface $object)
    {
        $this->meta_object = $object;
        return $this;
    }

    /**
     * Defines the object, that this action is to be performed upon (alias with namespace).
     * 
     * If not explicitly defined, the object of the widget calling the action (e.g. a button)
     * will be used automatically.
     *
     * @uxon-property object_alias
     * @uxon-type string
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setObjectAlias()
     */
    public function setObjectAlias($qualified_alias)
    {
        if ($object = $this->getWorkbench()->model()->getObject($qualified_alias)) {
            $this->setMetaObject($object);
        } else {
            throw new MetaObjectNotFoundError('Cannot load object "' . $qualified_alias . '" for action "' . $this->getAliasWithNamespace() . '"!', '6T5DJPP');
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::implementsInterface()
     */
    public function implementsInterface($interface)
    {
        if (! interface_exists($interface)){
            $interface = '\\exface\\Core\\Interfaces\\Actions\\' . $interface;
        }
        if ($this instanceof $interface) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::isUndoable()
     */
    public function isUndoable() : bool
    {
        if (is_null($this->is_undoable)) {
            if ($this instanceof iCanBeUndone) {
                return $this->is_undoable = true;
            } else {
                return $this->is_undoable = false;
            }
        }
        return $this->is_undoable;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setUndoable()
     */
    public function setUndoable($value)
    {
        $this->is_undoable = $value;
        return $this;
    }

    /**
     * 
     * {@inheritdoc}
     * @see iCanBeUndone::getUndoAction()
     */
    public function getUndoAction() : ActionInterface
    {
        if ($this->isUndoable()) {
            return ActionFactory::createFromString($this->exface, 'exface.Core.UndoAction', $this->getWidgetDefinedIn());
        }
    }

    /**
     * 
     * {@inheritdoc}
     * @see iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = new UxonObject();
        $uxon->setProperty('alias', $this->getAliasWithNamespace());
        if ($this->getWidgetDefinedIn()) {
            $uxon->setProperty('trigger_widget', $this->getWidgetDefinedIn()->createWidgetLink()->exportUxonObject());
        }
        if ($this->hasInputDataPreset()) {
            $uxon->setProperty('input_data_sheet',  $this->getInputDataPreset()->exportUxonObject());
        }
        $uxon->setProperty('disabled_behaviors', UxonObject::fromArray($this->getDisabledBehaviors()));
        
        if (empty($this->getInputMappers())){
            $input_mappers = new UxonObject();
            foreach ($this->getInputMappers() as $nr => $mapper){
                $input_mappers->setProperty($nr, $mapper->exportUxonObject());
            }
            $uxon->setProperty('input_mappers', $input_mappers);
        }
        
        return $uxon;
    }

    /**
     * 
     * @param string $event_name
     * @return ActionInterface
     */
    protected function dispatchEvent($event_name)
    {
        /* @var $event \exface\Core\Events\ActionEvent */
        $this->getApp()->getWorkbench()->eventManager()->dispatch(EventFactory::createActionEvent($this, $event_name));
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\ExfaceClassInterface::getWorkbench()
     * @return Workbench
     */
    public function getWorkbench()
    {
        return $this->exface;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setDisabledBehaviors()
     */
    public function setDisabledBehaviors(UxonObject $behavior_aliases)
    {
        $this->disabled_behaviors = $behavior_aliases->toArray();
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getDisabledBehaviors()
     */
    public function getDisabledBehaviors()
    {
        return $this->disabled_behaviors;
    }

    /**
     * Returns the translation string for the given message id.
     *
     * This is a shortcut for calling $this->getApp()->getTranslator()->translate(). Additionally it will automatically append an
     * action prefix to the given id: e.g. $action->translate('SOME_MESSAGE') will result in
     * $action->getApp()->getTranslator()->translate('ACTION.ALIAS.SOME_MESSAGE')
     *
     * @see Translation::translate()
     *
     * @param string $message_id            
     * @param array $placeholders            
     * @param float $number_for_plurification            
     * @return string
     */
    public function translate($message_id, array $placeholders = null, $number_for_plurification = null)
    {
        $message_id = trim($message_id);
        $key_prefix = 'ACTION.' . mb_strtoupper($this->getAlias()) . '.';
        if (mb_strpos($message_id, $key_prefix) !== 0) {
            $message_id = $key_prefix . $message_id;
        }
        return $this->getApp()->getTranslator()->translate($message_id, $placeholders, $number_for_plurification);
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getName()
     */
    public function getName()
    {
        if (is_null($this->name)) {
            $this->name = $this->translate('NAME');
        }
        return $this->name;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::hasName()
     */
    public function hasName()
    {
        return ! $this->name || substr($this->name, - 5) == '.NAME' ? false : true;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setName()
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeCopied::copy()
     */
    public function copy()
    {
        return clone $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getAutocommit()
     */
    public function getAutocommit()
    {
        return $this->autocommit;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setAutocommit()
     */
    public function setAutocommit($true_or_false)
    {
        $this->autocommit = $true_or_false ? true : false;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::isExactly()
     */
    public function isExactly($action_or_alias)
    {
        if ($action_or_alias instanceof ActionInterface) {
            $alias = $action_or_alias->getAliasWithNamespace();
        } else {
            $alias = $action_or_alias;
        }
        
        return strcasecmp($this->getAliasWithNamespace(), trim($alias)) === 0;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::is()
     */
    public function is($action_or_alias)
    {
        if ($action_or_alias instanceof ActionInterface){
            $class = get_class($action_or_alias);
            return $this instanceof $class;
        } elseif (is_string($action_or_alias)){
            if ($this->isExactly($action_or_alias)) {
                return true;
            }
            $selector = new ActionSelector($this->getWorkbench(), $action_or_alias);
            $class_name = $selector->getClassname();
            return $this instanceof $class_name;
        } else {
            throw new UnexpectedValueException('Invalid value "' . gettype($action_or_alias) .'" passed to "ActionInterface::is()": instantiated action or action alias with namespace expected!');
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::getInputMappers()
     */
    public function getInputMappers()
    {
        return $this->input_mappers;
    }
    
    /**
     * Defines transformation rules for input datasheets if they are not based on the object of the action.
     * 
     * Input mappers can be used to perform an action on an object, that it was
     * not explicitly made for - even if the objects are not related in any way.
     * 
     * You can define as many mappers as you like - each containing rules to
     * map data of its form-object to its to-object. These rules basically
     * define simple mappings from one expression to another.
     * 
     * For example, if you want to have an action, that will create a support
     * ticket for a selected purchase order, you will probably use a the
     * action CreateObjectDialog (or a derivative) based on the ticket object.
     * Now, you can use input mappers to prefill it with data from the (totally
     * unrelated) purchase order object:
     * 
     * {
     *  "input_mappers": [
     *      {
     *          "from_object_alias": "my.App.PurchaseOrder",
     *          "column_to_column_mappings": [
     *              {
     *                  "from": "LABEL",
     *                  "to": "TITLE"
     *              },
     *              {
     *                  "from": "CUSTOMER__PRIORITY__LEVEL",
     *                  "to": "PRIORITY__LEVEL"
     *              }
     *          ]
     *      }
     *  ]
     * }
     * 
     * In this example we map the label-attribute of the purchase order to the
     * title of the ticket. This will probably prefill our title field with
     * the order number and date (or whatever is set as label). We also map
     * the priority of the customer of the order to the ticket priority.
     * Assuming both attributes have identical numeric levels (probably 1, 2, 3),
     * this will result in high priority tickets for high priority customers.
     * 
     * You can now create an action in the model of your purchase orers, so
     * users can create tickets from every page showing orders. 
     * 
     * Alternatively you could create an action in the model of your tickets
     * with multiple mappers from different business objects: every time
     * the ticket-dialog opens, the system would see, if there is a suitable
     * mapper for the current input object and use it.
     * 
     * @uxon-property input_mappers
     * @uxon-type \exface\Core\CommonLogic\DataSheet\DataSheetMapper[]
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputMappers()
     */
    public function setInputMappers(UxonObject $uxon)
    {
        foreach ($uxon as $instance){
            $mapper = DataSheetMapperFactory::createFromUxon($this->getWorkbench(), $instance, null, $this->getMetaObject());         
            $this->addInputMapper($mapper);
        }
    }
    
    /**
     * Defines transformation rules for input data coming from the calling widget of this action.
     * 
     * This is a shortcut to specifying input_mappers, where an array needs to be created and
     * every mapper must have a from_object_alias defined. In contrast to input_mappers, you
     * can only define one mapper here and it will be automatically applied to data with
     * the meta object of the input widget of this action.
     * 
     * See description of the input_mappers property for more details. 
     * 
     * @uxon-property input_mapper
     * @uxon-type \exface\Core\CommonLogic\DataSheet\DataSheetMapper
     * 
     * @see setInputMappers()
     * @see \exface\Core\Interfaces\Actions\ActionInterface::setInputMapper()
     */
    public function setInputMapper(UxonObject $uxon)
    {
        if ($calling_widget = $this->getWidgetDefinedIn()) {
            if ($calling_widget instanceof iUseInputWidget) {
                $from_object = $calling_widget->getInputWidget()->getMetaObject();
            } else {
                $from_object = $calling_widget->getMetaObject();
            }
        } else {
            $this->getWorkbench()->getLogger()->warning('Cannot initialize input mapper for action "' . $this->getAliasWithNamespace() . '": no from-object defined and no calling widget to get it from!', [], $this);
            return $this;
        }
        $mapper = DataSheetMapperFactory::createFromUxon($this->getWorkbench(), $uxon, $from_object, $this->getMetaObject());
        return $this->addInputMapper($mapper);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::addInputMapper()
     */
    public function addInputMapper(DataSheetMapperInterface $mapper)
    {
        $this->input_mappers[] = $mapper;
        return $this;
    }
    
    /**
     * Gets the input data by merging the preset data with the task data and applying
     * appropriate input mappers.
     * 
     * NOTE: this can be a resource consuming task, so it is a good idea
     * to call this method only once!
     * 
     * @param TaskInterface $task
     * @return \exface\Core\Interfaces\DataSheets\DataSheetInterface
     */
    protected function getInputDataSheet(TaskInterface $task) : DataSheetInterface
    {
        // Get the current input data
        if ($task->hasInputData()) {
            // If the task has some, use it
            $sheet = $task->getInputData();
            // Merge it with the preset if it exists
            if ($this->hasInputDataPreset()) {
                $sheet = $this->getInputDataPreset()->importRows($sheet);
            } 
        } elseif ($this->hasInputDataPreset()) {
            // If the task has no data, use the preset data
            $sheet = $this->getInputDataPreset();
        } else {
            // If there is neither task nor preset data, create a new data sheet
            $sheet = DataSheetFactory::createFromObject($task->getMetaObject());    
        }
        
        // Apply the input mappers
        foreach ($this->getInputMappers() as $mapper){
            if ($mapper->getFromMetaObject()->is($sheet->getMetaObject())){
                return $mapper->map($sheet);
                break;
            }
        }
        
        return $sheet;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Actions\ActionInterface::isDefinedInWidget()
     */
    public function isDefinedInWidget(): bool
    {
        return is_null($this->trigger_widget) ? false : true;
    }
}
?>