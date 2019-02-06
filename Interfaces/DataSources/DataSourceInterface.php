<?php
namespace exface\Core\Interfaces\DataSources;

use exface\Core\CommonLogic\Workbench;
use exface\Core\CommonLogic\Model\Model;
use exface\Core\Interfaces\WorkbenchDependantInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\QueryBuilderInterface;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

interface DataSourceInterface extends WorkbenchDependantInterface
{

    /**
     *
     * @return DataConnectionInterface
     */
    public function getConnection() : DataConnectionInterface;
    
    public function setConnection(DataConnectionInterface $connection) : DataSourceInterface;

    /**
     *
     * @return string
     */
    public function getId() : string;

    /**
     *
     * @return string
     */
    public function getQueryBuilder() : QueryBuilderInterface;

    /**
     *
     * @param string $value            
     */
    public function setQueryBuilder(QueryBuilderInterface $qb) : DataSourceInterface;

    /**
     * Returns TRUE if write-opertaions are allowed on the data source with it's 
     * current connection and FALSE otherwise.
     *
     * @return boolean
     */
    public function isWritable();

    /**
     * Set to FALSE to mark this data source as read only.
     *
     * @param boolean $value            
     * @return DataSourceInterface
     */
    public function setWritable($value);
    
    /**
     * Returns TRUE if read-operations are allowed on the data source and FALSE otherwise.
     * 
     * @return boolean
     */
    public function isReadable();
    
    /**
     * Set to TRUE to prevent read-operations on this data source.
     * 
     * @param boolean $true_or_false
     * @return DataSourceInterface
     */
    public function setReadable($true_or_false);

    /**
     * 
     * @return string
     */
    public function getName() : string;
    
    public function read(DataSheetInterface &$dataSheet);
    
    public function count(DataSheetInterface &$dataSheet, DataTransactionInterface $transaction) : int;
    
    public function create(DataSheetInterface &$dataSheet, DataTransactionInterface $transaction);
    
    public function update(DataSheetInterface &$dataSheet, DataTransactionInterface $transaction);
    
    public function delete(DataSheetInterface &$dataSheet, DataTransactionInterface $transaction);
}
?>