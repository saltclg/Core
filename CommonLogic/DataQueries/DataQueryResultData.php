<?php
namespace exface\Core\CommonLogic\DataQueries;

use exface\Core\Interfaces\DataSources\DataQueryResultDataInterface;

class DataQueryResultData implements DataQueryResultDataInterface
{
    private $rows = null;
    private $hasMoreRows = null;
    private $aggregationRows = null;
    private $totalRowCount = null;
    private $affectedRowCount = null;
    
    public function __construct(array $resultRows, int $affectedRowCount, bool $hasMoreRows = false, int $totalRowCount = null, array $aggregationRows = [])
    {
        $this->rows = $resultRows;
        $this->hasMoreRows = $hasMoreRows;
        $this->aggregationRows = $aggregationRows;
        $this->totalRowCount = $totalRowCount;
        $this->affectedRowCount = $affectedRowCount;
    }
    
    public function getResultRows() : array
    {
        return $this->rows;
    }
    
    public function hasMoreRows() : bool
    {
        return $this->hasMoreRows;
    }
    
    public function getTotalsRows() : array
    {
        return $this->aggregationRows;
    }
    
    public function getAllRowsCounter() : ?int
    {
        if ($this->totalRowCount === null && $this->hasMoreRows() === false) {
            return $this->countResultRows();
        }
        return $this->totalRowCount;
    }
    
    public function countResultRows() : int
    {
        return count($this->rows);
    }
    
    public function hasResultRows() : bool
    {
        return empty($this->rows) === false;
    }
    
    public function getAffectedRowsCounter() : ?int
    {
        return $this->affectedRowCount;
    }
}