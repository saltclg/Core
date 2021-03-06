<?php
namespace exface\Core\Formulas;

/**
 * Sums all its arguments.
 * Analogous to Excel's SUM() function.
 * E.g.: SUM(ALIAS1, ALIAS2, ALIAS3...)
 *
 * @author Andrej Kabachnik
 *        
 */
class Sum extends \exface\Core\CommonLogic\Model\Formula
{

    function run()
    {
        $return = 0;
        for ($i = 0; $i < func_num_args(); $i ++) {
            $return += func_get_arg($i);
        }
        return $return;
    }
}
?>