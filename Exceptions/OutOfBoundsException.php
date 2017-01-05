<?php
namespace exface\Core\Exceptions;

use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;

/**
 * Exception thrown if a value is not a valid key. This represents errors that cannot be detected at compile time.
 * 
 * @author Andrej Kabachnik
 *
 */
class OutOfBoundsException extends \OutOfBoundsException implements ErrorExceptionInterface, \Throwable {
	
	use ExceptionTrait;
	
}
?>