<?php
namespace exface\Core\Exceptions\Model;

use exface\Core\Exceptions\UnexpectedValueException;
use exface\Core\Interfaces\Exceptions\MetaObjectExceptionInterface;
use exface\Core\CommonLogic\Model\Object;

/**
 * Exception thrown if a requested attribute cannot be found for the given object. This will mostly happen if 
 * an attribute alias is misspelled in UXON.
 *
 * @author Andrej Kabachnik
 *
 */
class MetaAttributeNotFoundError extends UnexpectedValueException implements MetaObjectExceptionInterface {
	
	use MetaObjectExceptionTrait;
	
	/**
	 *
	 * @param Object $meta_object
	 * @param string $message
	 * @param string $alias
	 * @param \Throwable $previous
	 */
	public function __construct (Object $meta_object, $message, $alias = null, $previous = null) {
		parent::__construct($message, null, $previous);
		$this->set_alias($alias);
		$this->set_meta_object($meta_object);
	}
	
}
?>