<?php
namespace exface\Core\QueryBuilders;

use exface\Core\Exceptions\QueryBuilderException;
use exface\Core\CommonLogic\Model\RelationPath;
use exface\Core\DataTypes\DateDataType;
use exface\Core\CommonLogic\Model\Aggregator;
use exface\Core\DataTypes\AggregatorFunctionsDataType;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;
use exface\Core\Interfaces\DataSources\DataConnectionInterface;
use exface\Core\CommonLogic\DataQueries\DataQueryResultData;
use exface\Core\Interfaces\DataSources\DataQueryResultDataInterface;

/**
 * A query builder for MySQL.
 *
 * See `AbstractSqlBuilder` for available data address options!
 * 
 * @see AbstractSqlBuilder
 *
 * @author Andrej Kabachnik
 *        
 */
class MySqlBuilder extends AbstractSqlBuilder
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\QueryBuilders\AbstractSqlBuilder::getShortAliasMaxLength()
     */
    protected function getShortAliasMaxLength() : int
    {
        return 64;
    }
    
    /**
     * In MySQL the select query is pretty straight-forward: there is no need to create nested queries,
     * since MySQL natively supports selecting pages (LIMIT).
     * However, if aggregators (GROUP BY) are used, we still need
     * to distinguish between core and enrichment elements in order to join enrchichment stuff after all
     * the aggregating had been done.
     *
     * @see AbstractSqlBuilder::buildSqlQuerySelect()
     */
    public function buildSqlQuerySelect()
    {
        $where = '';
        $having = '';
        $group_by = '';
        $group_safe_attribute_aliases = array();
        $order_by = '';
        $selects = array();
        $select = '';
        $select_comment = '';
        $joins = array();
        $join = '';
        $enrichment_selects = [];
        $enrichment_select = '';
        $enrichment_joins = array();
        $enrichment_join = '';
        $limit = '';
        
        // WHERE & HAVING
        $where = $this->buildSqlWhere($this->getFilters());
        $having = $this->buildSqlHaving($this->getFilters());
        $joins = $this->buildSqlJoins($this->getFilters());
        
        // Object data source property SQL_SELECT_WHERE -> WHERE
        if ($custom_where = $this->getMainObject()->getDataAddressProperty('SQL_SELECT_WHERE')) {
            $where = $this->appendCustomWhere($where, $custom_where);
        }
        
        $where = $where ? "\n WHERE " . $where : '';
        $having = $having ? "\n HAVING " . $having : '';
        
        // GROUP BY
        $group_uid_alias = '';
        foreach ($this->getAggregations() as $qpart) {
            $group_by .= ', ' . $this->buildSqlGroupBy($qpart);
            if (! $group_uid_alias) {
                if ($rel_path = $qpart->getAttribute()->getRelationPath()->toString()) {
                    $group_uid_alias = RelationPath::relationPathAdd($rel_path, $this->getMainObject()->getRelatedObject($rel_path)->getUidAttributeAlias());
                }
            }
        }
        $group_by = $group_by ? ' GROUP BY ' . substr($group_by, 2) : '';
        if ($group_uid_alias) {
            // $this->addAttribute($group_uid_alias);
        }
        
        // SELECT
        /* @var $qpart \exface\Core\CommonLogic\QueryBuilder\QueryPartSelect */
        foreach ($this->getAttributes() as $qpart) {
            $qpartAttr = $qpart->getAttribute();
            // First see, if the attribute has some kind of special data type (e.g. binary)
            if ($qpartAttr->getDataAddressProperty('SQL_DATA_TYPE') == 'binary') {
                $this->addBinaryColumn($qpart->getAlias());
            }
            
            if ($group_by && $qpartAttr->isExactly($qpartAttr->getObject()->getUidAttribute()) && ! $qpart->getAggregator()) {
                // If the query has a GROUP BY, we need to put the UID-Attribute in the core select as well as in the enrichment select
                // otherwise the enrichment joins won't work! Be carefull to apply this rule only to the plain UID column, not to columns
                // using the UID with aggregate functions
                $selects[] = $this->buildSqlSelect($qpart, null, null, null, new Aggregator($this->getWorkbench(), AggregatorFunctionsDataType::MAX));
                $enrichment_selects[] = $this->buildSqlSelect($qpart, 'EXFCOREQ', $this->getShortAlias($qpart->getColumnKey()));
            } elseif (! $group_by || $qpart->getAggregator() || $this->isAggregatedBy($qpart)) {
                // If we are not aggregating or the attribute has a group function, add it regulary
                $selects[] = $this->buildSqlSelect($qpart);
                $joins = array_merge($joins, $this->buildSqlJoins($qpart));
                $group_safe_attribute_aliases[] = $qpartAttr->getAliasWithRelationPath();
            } elseif ($this->isObjectGroupSafe($qpartAttr->getObject()) === true) {
                // If aggregating, also add attributes, that are aggregated over or can be assumed unique due to set filters
                // FIXME allways putting selects for attributes of related group-safe object in the enrichment select will
                // probably break sorting over these attributes because sorting is done in the core query too...
                $rels = $qpart->getUsedRelations();
                $first_rel = false;
                if (! empty($rels)) {
                    $first_rel = reset($rels);
                    $first_rel_qpart = $this->addAttribute($first_rel->getAlias());
                    // IDEA this does not support relations based on custom sql. Perhaps this needs to change
                    $selects[] = $this->buildSqlSelect($first_rel_qpart, null, null, $first_rel_qpart->getAttribute()->getDataAddress(), ($group_by ? new Aggregator($this->getWorkbench(), AggregatorFunctionsDataType::MAX) : null));
                }
                $enrichment_selects[] = $this->buildSqlSelect($qpart);
                $enrichment_joins = array_merge($enrichment_joins, $this->buildSqlJoins($qpart, 'exfcoreq'));
                $joins = array_merge($joins, $this->buildSqlJoins($qpart));
                $group_safe_attribute_aliases[] = $qpartAttr->getAliasWithRelationPath();
            } elseif ($group_by && $this->getAggregation($qpartAttr->getRelationPath()->toString())) {
                // If aggregating, also add attributes, that belong directly to objects, we are aggregating 
                // over (they can be assumed unique too, since their object is unique per row)
                // FIXME it should be possible to integrate this into the if-branch with isObjectGroupSafe())
                $selects[] = $this->buildSqlSelect($qpart, null, null, null, new Aggregator($this->getWorkbench(), AggregatorFunctionsDataType::MAX));
                $joins = array_merge($joins, $this->buildSqlJoins($qpart));
                $group_safe_attribute_aliases[] = $qpartAttr->getAliasWithRelationPath();
            } else {
                $select_comment .= '-- ' . $qpart->getAlias() . ' is ignored because it is not group-safe or ambiguously defined' . "\n";
            }
        }
        
        // Core SELECT
        $select = implode(', ', array_unique(array_filter($selects)));
        $select_comment = $select_comment ? "\n" . $select_comment : '';
        
        // Enrichment SELECT
        $enrichment_select = implode(', ', array_unique(array_filter($enrichment_selects)));
        $enrichment_select = 'EXFCOREQ' . $this->getAliasDelim() . '*' . ($enrichment_select ? ', ' . $enrichment_select : '');
        
        // FROM
        $from = $this->buildSqlFrom();
        
        // JOINs
        $join = implode(' ', $joins);
        $enrichment_join = implode(' ', $enrichment_joins);
        
        // ORDER BY
        foreach ($this->getSorters() as $qpart) {
            // A sorter can only be used, if there is no GROUP BY, or the sorted attribute has unique values within the group
            /*
             * if (!$this->getAggregations() || in_array($qpart->getAttribute()->getAliasWithRelationPath(), $group_safe_attribute_aliases)){
             * $order_by .= ', ' . $this->buildSqlOrderBy($qpart);
             * }
             */
            $order_by .= ', ' . $this->buildSqlOrderBy($qpart);
        }
        $order_by = $order_by ? ' ORDER BY ' . substr($order_by, 2) : '';
        
        $distinct = $this->getSelectDistinct() ? 'DISTINCT ' : '';
        
        if ($this->getLimit() > 0) {
            // Increase limit by one to check if there are more rows (see AbstractSqlBuilder::read())
            $limit = ' LIMIT ' . ($this->getLimit()+1) . ' OFFSET ' . $this->getOffset();
        }
        
        if ($this->isEnrichmentAllowed() && (($group_by && $where) || $this->getSelectDistinct())) {
            $query = "\n SELECT " . $distinct . $enrichment_select . $select_comment . " FROM (SELECT " . $select . " FROM " . $from . $join . $where . $group_by . $having . $order_by . ") EXFCOREQ " . $enrichment_join . $order_by . $limit;
        } else {
            $query = "\n SELECT " . $distinct . $select . $select_comment . " FROM " . $from . $join . $where . $group_by . $order_by . $having . $limit;
        }
        
        return $query;
    }
    
    /**
     * Returns TRUE if this query can use core/enrichment separation and FALSE otherwise.
     * 
     * Override this method to control enrichment in special constellations.
     * 
     * @return bool
     */
    protected function isEnrichmentAllowed() : bool
    {
        return true;
    }

    public function buildSqlQueryTotals()
    {
        $totals_joins = array();
        $totals_core_selects = array();
        $totals_selects = array();
        if (count($this->getTotals()) > 0) {
            // determine all joins, needed to perform the totals functions
            foreach ($this->getTotals() as $qpart) {
                $totals_selects[] = $this->buildSqlSelect($qpart, 'EXFCOREQ', $this->getShortAlias($qpart->getColumnKey()), null, $qpart->getTotalAggregator());
                $totals_core_selects[] = $this->buildSqlSelect($qpart);
                $totals_joins = array_merge($totals_joins, $this->buildSqlJoins($qpart));
            }
        }
        
        if ($group_by) {
            $totals_core_selects[] = $this->buildSqlSelect($this->getAttribute($this->getMainObject()->getUidAttributeAlias()), null, null, null, new Aggregator($this->getWorkbench(), AggregatorFunctionsDataType::MAX));
        }
        
        // filters -> WHERE
        $totals_where = $this->buildSqlWhere($this->getFilters());
        $totals_having = $this->buildSqlHaving($this->getFilters());
        $totals_joins = array_merge($totals_joins, $this->buildSqlJoins($this->getFilters()));
        // Object data source property SQL_SELECT_WHERE -> WHERE
        if ($custom_where = $this->getMainObject()->getDataAddressProperty('SQL_SELECT_WHERE')) {
            $totals_where = $this->appendCustomWhere($totals_where, $custom_where);
        }
        // GROUP BY
        foreach ($this->getAggregations() as $qpart) {
            $group_by .= ', ' . $this->buildSqlGroupBy($qpart);
            $totals_joins = array_merge($totals_joins, $this->buildSqlJoins($qpart));
        }
        
        $totals_select = count($totals_selects) ? ', ' . implode(",\n", $totals_selects) : '';
        $totals_core_select = implode(",\n", $totals_core_selects);
        $totals_from = $this->buildSqlFrom();
        $totals_join = implode("\n ", $totals_joins);
        $totals_where = $totals_where ? "\n WHERE " . $totals_where : '';
        $totals_having = $totals_having ? "\n WHERE " . $totals_having : '';
        $totals_group_by = $group_by ? "\n GROUP BY " . substr($group_by, 2) : '';
        
        // This is a bit of a dirty hack to get the COUNT(*) right if there is a GROUP BY. Just enforce the use of a query with enrichment
        if ($group_by && ! $totals_core_select) {
            $totals_core_select = '1';
        }
        
        if ($totals_core_select) {
            $totals_query = "\n SELECT COUNT(*) AS EXFCNT " . $totals_select . " FROM (SELECT " . $totals_core_select . ' FROM ' . $totals_from . $totals_join . $totals_where . $totals_group_by . $totals_having . ") EXFCOREQ";
        } else {
            $totals_query = "\n SELECT COUNT(*) AS EXFCNT FROM " . $totals_from . $totals_join . $totals_where . $totals_group_by . $totals_having;
        }
        
        return $totals_query;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\QueryBuilders\AbstractSqlBuilder::prepareWhereValue()
     */
    protected function prepareWhereValue($value, DataTypeInterface $data_type, $sql_data_type = NULL)
    {
        if ($data_type instanceof DateDataType) {
            $output = "{ts '" . $value . "'}";
        } else {
            $output = parent::prepareWhereValue($value, $data_type, $sql_data_type);
        }
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\QueryBuilders\AbstractSqlBuilder::buildSqlSelectNullCheckFunctionName()
     */
    protected function buildSqlSelectNullCheckFunctionName()
    {
        return 'IFNULL';
    }    

    /**
     * Special DELETE builder for MySQL because MySQL does not support table aliases in the DELETE query.
     * Thus, they must be removed from all the generated filters and other parts of the query.
     *
     * @see AbstractSqlBuilder::delete()
     */
    function delete(DataConnectionInterface $data_connection) : DataQueryResultDataInterface
    {
        // filters -> WHERE
        // Relations (joins) are not supported in delete clauses, so check for them first!
        if (count($this->getFilters()->getUsedRelations()) > 0) {
            throw new QueryBuilderException('Filters over attributes of related objects are not supported in DELETE queries!');
        }
        /* This was an unfinished attempt to overcome the filtering problem at least for filters like RELATION__UID.
         * didn't have time to test it thoroughly. May come back later.
        foreach ($this->getFilters()->getFilters() as $qpart) {
            $rels = $qpart->getUsedRelations();
            switch (count($rels)) {
                case 0: continue;
                case 1:
                    $rel = reset($rels);
                    if ($rel->isForwardRelation() && $this->getMainObject()->isExactly($rel->getLeftObject())) {
                        $this->getFilters()->removeFilter($qpart);
                        $this->addFilterFromString($rel->getLeftKeyAttribute()->getAlias(), $qpart->getCompareValue(), $qpart->getComparator());
                        break;
                    }
                default:
                    throw new QueryBuilderException('Filters over attributes of related objects are not supported in DELETE queries!');
            }
        }*/
        $where = $this->buildSqlWhere($this->getFilters());
        $where = $where ? "\n WHERE " . $where : '';
        if (! $where)
            throw new QueryBuilderException('Cannot perform update on all objects "' . $this->main_object->getAlias() . '"! Forbidden operation!');
        
        $sql = 'DELETE FROM ' . $this->getMainObject()->getDataAddress() . str_replace($this->getMainObject()->getAlias() . $this->getAliasDelim(), '', $where);
        $query = $data_connection->runSql($sql);
        $cnt = $query->countAffectedRows();
        
        $query->freeResult();
        
        return new DataQueryResultData([], $cnt);
    }
}
?>