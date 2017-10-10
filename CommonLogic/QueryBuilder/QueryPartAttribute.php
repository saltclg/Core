<?php
namespace exface\Core\CommonLogic\QueryBuilder;

use exface\Core\Exceptions\QueryBuilderException;
use exface\Core\CommonLogic\Model\RelationPath;
use exface\Core\CommonLogic\DataSheets\DataAggregation;
use exface\Core\Interfaces\Model\AggregatorInterface;
use exface\Core\Exceptions\Model\MetaAttributeNotFoundError;

class QueryPartAttribute extends QueryPart
{

    private $aggregator;

    private $used_relations = null;
    
    private $placeholders = null;

    function __construct($alias, AbstractQueryBuilder $query)
    {
        parent::__construct($alias, $query);
        
        if (! $attr = $query->getMainObject()->getAttribute($alias)) {
            throw new QueryBuilderException('Attribute "' . $alias . '" of object "' . $query->getMainObject()->getAlias() . '" not found!');
        } else {
            $this->setAttribute($attr);
        }
        
        if ($aggr = DataAggregation::getAggregatorFromAlias($alias)){
            $this->aggregator = $aggr;
        }
    }
    
    public function hasPlaceholdersInDataAddress()
    {
        return count($this->getPlaceholdersInDataAddress()) > 0 ? true : false;
    }
    
    public function getPlaceholdersInDataAddress()
    {
        if (is_null($this->placeholders)){
            // Get all placeholders from the data address
            $phs = $this->getWorkbench()->utils()->findPlaceholdersInString($this->getAttribute()->getDataAddress());
            // Filter away static placeholders starting with "~" (e.g. "~alias").
            $this->placeholders = array_filter($phs, function($ph){return substr($ph, 0, 1) !== '~';});
        }
        return $this->placeholders;
    }
    
    public function getPlaceholderQueryParts()
    {
        $qparts = [];
        foreach ($this->getPlaceholdersInDataAddress() as $ph){
            try {
                $this->getQuery()->getMainObject()->getAttribute(RelationPath::relationPathAdd($this->getAttribute()->getRelationPath()->toString(), $ph));
            } catch (MetaAttributeNotFoundError $e){
                throw new QueryBuilderException('Cannot use placeholder [#' . $ph . '#] in attribute "' . $this->getAttribute()->getAliasWithRelationPath() . '": no matching attribute found for query base object ' . $this->getQuery()->getMainObject()->getAliasWithNamespace() . '!', null, $e);
            }
            $qparts[] = new self($ph, $this->getQuery());
        }
        return $qparts;
    }

    /**
     *
     * @see \exface\Core\CommonLogic\QueryBuilder\QueryPart::getUsedRelations()
     */
    public function getUsedRelations($relation_type = null)
    {
        $rels = array();
        // first check the cache
        if (is_array($this->used_relations)) {
            $rels = $this->used_relations;
        } else {
            // Get relations of the attribute itself
            $rels = $this->getUsedRelationsFromPath($this->getAttribute()->getRelationPath());
            
            // Add relations from placeholders
            foreach ($this->getPlaceholderQueryParts() as $qpart){
                $rels = array_merge($rels, $this->getUsedRelationsFromPath($qpart->getAttribute()->getRelationPath()));
            }
            
            // Cache the result
            $this->used_relations = $rels;
        }
        
        // if looking for a specific relation type, remove all the others
        if ($relation_type) {
            foreach ($rels as $alias => $rel) {
                if ($rel->getType() != $relation_type) {
                    unset($rels[$alias]);
                }
            }
        }
        
        return $rels;
    }
    
    protected function getUsedRelationsFromPath(RelationPath $relation_path)
    {
        $rels = [];
        $last_alias = '';
        foreach ($relation_path->getRelations() as $rel) {
            $alias = $rel->getAlias();
            $rels[$last_alias . $alias] = $this->getQuery()->getMainObject()->getRelation($last_alias . $alias);
            $last_alias .= $alias . RelationPath::getRelationSeparator();
        }
        return $rels;
    }

    /**
     * Returns the aggregator used to calculate values in this query part.
     * 
     * E.g. for POSITION__VALUE:SUM it would return SUM (in the form of an
     * instantiated aggregator).
     * 
     * @return AggregatorInterface
     */
    public function getAggregator()
    {
        return $this->aggregator;
    }

    public function setAggregator(AggregatorInterface $value)
    {
        $this->aggregator = $value;
    }

    public function getDataAddressProperty($property_key)
    {
        return $this->getAttribute()->getDataAddressProperty($property_key);
    }

    public function setDataAddressProperty($property_key, $value)
    {
        return $this->getAttribute()->setDataAddressProperty($property_key, $value);
    }

    /**
     * Returns the data source specific address of the attribute represented by this query part.
     * Depending
     * on the data source, this can be an SQL column name, a file name, etc.
     *
     * @return string
     */
    public function getDataAddress()
    {
        return $this->getAttribute()->getDataAddress();
    }

    public function getMetaModel()
    {
        return $this->getAttribute()->getModel();
    }

    /**
     * Parses the alias of this query part as an ExFace expression and returns the expression object
     *
     * @return \exface\Core\Interfaces\Model\ExpressionInterface
     */
    public function getExpression()
    {
        return $this->getWorkbench()->model()->parseExpression($this->getAlias(), $this->getQuery()->getMainObject());
    }

    public function rebase(AbstractQueryBuilder $new_query, $relation_path_to_new_base_object)
    {
        $qpart = clone $this;
        $qpart->setQuery($new_query);
        $new_expression = $this->getExpression()->rebase($relation_path_to_new_base_object);
        $qpart->used_relations = array();
        $qpart->setAttribute($new_expression->getAttribute());
        $qpart->setAlias($new_expression->toString());
        return $qpart;
    }
}
?>