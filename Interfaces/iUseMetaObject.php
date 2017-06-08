<?php
namespace exface\Core\Interfaces;

use exface\Core\CommonLogic\Model\Object;

interface iUseMetaObject
{

    /**
     * Returns the meta object, this instance belongs to.
     *
     * @return Object
     */
    public function getMetaObject();
}
?>