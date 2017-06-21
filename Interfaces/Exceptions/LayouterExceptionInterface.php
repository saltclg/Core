<?php
namespace exface\Core\Interfaces\Exceptions;

use exface\Core\Interfaces\Layouters\LayouterInterface;

Interface LayouterExceptionInterface extends ExceptionInterface
{

    /**
     *
     * @param LayouterInterface $layout            
     * @param string $message            
     * @param string $code            
     * @param \Throwable $previous            
     */
    public function __construct(LayouterInterface $layout, $message, $code = null, $previous = null);

    /**
     *
     * @return LayouterInterface
     */
    public function getLayout();

    /**
     *
     * @param LayouterInterface $layout            
     * @return LayoutExceptionInterface
     */
    public function setLayout(LayouterInterface $layout);
}
?>