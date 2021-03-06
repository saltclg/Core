<?php
namespace exface\Core\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\Core\CommonLogic\DataQueries\FileContentsDataQuery;
use exface\Core\Exceptions\QueryBuilderException;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Interfaces\Model\MetaAttributeInterface;
use exface\Core\Interfaces\DataSources\DataConnectionInterface;
use exface\Core\Interfaces\DataSources\DataQueryResultDataInterface;
use exface\Core\CommonLogic\DataQueries\DataQueryResultData;

/**
 * A query builder to the raw contents of a file.
 * This is the base for many specific query builders like the CsvBuilder, etc.
 *
 *
 * @author Andrej Kabachnik
 *        
 */
class FileContentsBuilder extends AbstractQueryBuilder
{
    /**
     *
     * @return FileContentsDataQuery
     */
    protected function buildQuery()
    {
        $query = new FileContentsDataQuery();
        $query->setPathRelative($this->replacePlaceholdersInPath($this->getMainObject()->getDataAddress()));
        return $query;
    }

    protected function getFileProperty(FileContentsDataQuery $query, $data_address)
    {
        switch (mb_strtoupper($data_address)) {
            case '_FILEPATH':
                return $query->getPathAbsolute();
            case '_FILEPATH_RELATIVE':
                return $query->getPathRelative();
            case '_CONTENTS':
                return file_get_contents($query->getPathAbsolute());
            default:
                return false;
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::read()
     */
    public function read(DataConnectionInterface $data_connection) : DataQueryResultDataInterface
    {
        $result_rows = array();
        $query = $this->buildQuery();
        if (is_null($data_connection)) {
            $data_connection = $this->getMainObject()->getDataConnection();
        }
        
        $data_connection->query($query);
        
        foreach ($this->getAttributes() as $qpart) {
            if ($this->getFileProperty($query, $qpart->getDataAddress())) {
                $result_rows[$qpart->getColumnKey()] = $this->getFileProperty($query, $qpart->getDataAddress());
            }
        }
        
        $resultTotalRows = count($result_rows);
        
        $this->applyFilters($result_rows);
        $this->applySorting($result_rows);
        $this->applyPagination($result_rows);
        
        $cnt = count($result_rows);
        return new DataQueryResultData($result_rows, $cnt, ($resultTotalRows > $cnt+$this->getOffset()), $resultTotalRows);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::count()
     */
    public function count(DataConnectionInterface $data_connection) : DataQueryResultDataInterface
    {
        return $this->read($data_connection);
    }

    /**
     * Looks for placeholders in the give path and replaces them with values from the corresponding filters.
     * Returns the given string with all placeholders replaced or FALSE if some placeholders could not be replaced.
     *
     * @param string $path            
     * @return string|boolean
     */
    protected function replacePlaceholdersInPath($path)
    {
        foreach (StringDataType::findPlaceholders($path) as $ph) {
            if ($ph_filter = $this->getFilter($ph)) {
                if (! is_null($ph_filter->getCompareValue())) {
                    $path = str_replace('[#' . $ph . '#]', $ph_filter->getCompareValue(), $path);
                } else {
                    throw new QueryBuilderException('Filter "' . $ph_filter->getAlias() . '" required for "' . $path . '" does not have a value!');
                }
            } else {
                // If at least one placeholder does not have a corresponding filter, return false
                throw new QueryBuilderException('No filter found in query for placeholder "' . $ph . '" required for "' . $path . '"!');
            }
        }
        return $path;
    }
    
    /**
     * The FileContentsBuilder can only handle attributes of one object - no relations (JOINs) supported!
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::canReadAttribute()
     */
    public function canReadAttribute(MetaAttributeInterface $attribute) : bool
    {
        return $attribute->getRelationPath()->isEmpty();
    }
}
?>