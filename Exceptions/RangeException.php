<?php
namespace exface\Core\Exceptions;

use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;

/**
 * Exception thrown to indicate range errors during program execution. 
 * Normally this means there was an arithmetic error other than under/overflow. This is the runtime version of DomainException.
 * 
 * @author Andrej Kabachik
 *
 */
class RangeException extends \RangeException implements ErrorExceptionInterface, \Throwable {
	
	use ExceptionTrait;
	
}
?>