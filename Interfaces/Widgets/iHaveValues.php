<?php
namespace exface\Core\Interfaces\Widgets;

use exface\Core\Interfaces\Model\ExpressionInterface;

interface iHaveValues extends iHaveValue
{

    /**
     *
     * @return array
     */
    public function getValues();

    /**
     *
     * @param ExpressionInterface|string $expression_or_delimited_list            
     */
    public function setValues($expression_or_delimited_list);

    /**
     *
     * @param array $values            
     */
    public function setValuesFromArray(array $values);
}