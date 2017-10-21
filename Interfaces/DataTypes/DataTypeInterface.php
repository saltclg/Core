<?php
namespace exface\Core\Interfaces\DataTypes;

use exface\Core\Exceptions\DataTypes\DataTypeCastingError;
use exface\Core\CommonLogic\Model\Model;
use exface\Core\Interfaces\ExfaceClassInterface;
use exface\Core\Interfaces\AliasInterface;
use exface\Core\Interfaces\NameResolverInterface;
use exface\Core\Interfaces\AppInterface;
use exface\Core\Interfaces\iCanBeCopied;
use exface\Core\Interfaces\iCanBeConvertedToUxon;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Exceptions\DataTypes\DataTypeValidationError;
use exface\Core\DataTypes\SortingDirectionsDataType;
use exface\Core\Interfaces\Model\MetaModelPrototypeInterface;

interface DataTypeInterface extends ExfaceClassInterface, AliasInterface, iCanBeCopied, iCanBeConvertedToUxon, MetaModelPrototypeInterface
{

    /**
     * 
     * @param Workbench $workbench
     * @param mixed $value
     * @param NameResolverInterface $name_resolver
     * @param UxonObject $configuration
     */
    public function __construct(Workbench $workbench, $value = null, NameResolverInterface $name_resolver = null, UxonObject $configuration = null);

    /**
     * @param string $string
     * @return DataTypeInterface
     */
    public function setAlias($string);
    
    /**
     *
     * @return Model
     */
    public function getModel();

    /**
     * Returns the string name of the data type (e.g.
     * Number, String, etc.)
     *
     * @return string
     */
    public function getName();
    
    /**
     * @return DataTypeInterface
     */
    public function setName($string);

    /**
     * Returns TRUE if the current data type equals is derived from the given one (e.g.
     * Integer::is(Number) = true) and FALSE otherwise.
     *
     * @param DataTypeInterface|string $data_type_or_resolvable_name
     * @return boolean
     */
    public function is($data_type_or_resolvable_name);

    /**
     * Returns a normalized representation of the given string matching the data prototype, but 
     * does not check any configurable resrictions of the data type instance.
     * 
     * In other words, the string is made data prototype conform. That's all we can do without
     * instantiating a concrete data type. On the other hand, any valid value of any data type
     * based on this prototype will pass casting without being modified.
     * 
     * E.g. DateDataType::cast('21.9.1984') = 1984-09-21.
     * 
     * @see DataTypeInterface::parse($string) for a similar method for instantiated types.
     *
     * @param string $string            
     * @throws DataTypeCastingError
     * @return string
     */
    public static function cast($string);
    
    /**
     * Returns a normalized representation of the given string mathing all the rules defined in the
     * data type.
     * 
     * While the static cast() method only makes the value compatible with the prototype, parse()
     * will make sure it matches all rules of the data type - including those defined in it's model.
     * 
     * E.g. NumberDataType::cast(1,5523) = 1.5523, but exface.Core.NumberNatural->parse(1,5523) = 1,
     * because the natural number model not only casts anything to a number, but also rounds it to
     * the a whole number.
     *
     * @param string $string
     * @throws DataTypeValidationError
     * @return string
     */
    public function parse($string);
    
    /**
     * Returns the unique error code (error model alias) used for parsing errors of this data type.
     * 
     * @return string
     */
    public function getValidationErrorCode();
    
    /**
     * Sets the unique error code (error model alias) used for parsing errors of this data type.
     * 
     * @param string $string
     * @return DataTypeInterface
     */
    public function setValidationErrorCode($string);
    
    /**
     * Returns the text explaining validation errors (e.g. "Model entity aliases must not start with '_' or '~').
     * 
     * @return string
     */
    public function getValidationErrorText();
    
    
    /**
     * Changes the explanation text for validation errors.
     * 
     * @param string $string
     * @return DataTypeInterface
     */
    public function setValidationErrorText($string);

    /**
     * Returns TRUE if the given value matches the data type (and thus can be parsed) or FALSE otherwise.
     *
     * @param mixed $string            
     * @return boolean
     */
    public function isValidValue($string);
    
    /**
     * 
     * @return SortingDirectionsDataType
     */
    public function getDefaultSortingDirection();
    
    /**
     * Returns the name resolver for this data type.
     * 
     * @return NameResolverInterface
     */
    public function getNameResolver();
    
    /**
     * Returns the app, to which this data type belongs to.
     * 
     * NOTE: if the model of this data type belongs to another app, than its prototype, this method
     * will return the app of the model. 
     * 
     * @return AppInterface
     */
    public function getApp();
    
    /**
     * 
     * @param AppInterface $app
     * @return DataTypeInterface
     */
    public function setApp(AppInterface $app);
    
    /**
     * @return string
     */
    public function getShortDescription();
    
    /**
     * 
     * @param string $text
     * @return DataTypeInterface
     */
    public function setShortDescription($text);
    
    /**
     * 
     * @param UxonObject $uxon
     * @return DataTypeInterface
     */
    public function setDefaultEditorUxon(UxonObject $uxon);
    
    /**
     * 
     * @param UxonObject $uxon
     * @return DataTypeInterface
     */
    public function setDefaultEditorWidget(UxonObject $uxon);
    
    /**
     * @return UxonObject
     */
    public function getDefaultEditorUxon();
}
?>