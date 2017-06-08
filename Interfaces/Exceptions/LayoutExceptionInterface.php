<?php
namespace exface\Core\Interfaces\Exceptions;

use exface\Core\Interfaces\Layouts\LayoutInterface;

Interface LayoutExceptionInterface extends ExceptionInterface
{

    /**
     *
     * @param LayoutInterface $layout            
     * @param string $message            
     * @param string $code            
     * @param \Throwable $previous            
     */
    public function __construct(LayoutInterface $layout, $message, $code = null, $previous = null);

    /**
     *
     * @return LayoutInterface
     */
    public function getLayout();

    /**
     *
     * @param LayoutInterface $layout            
     * @return LayoutExceptionInterface
     */
    public function setLayout(LayoutInterface $layout);
}
?>