<?php
namespace exface\Core\CommonLogic\DataSheets;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\Traits\ImportUxonObjectTrait;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\CommonLogic\Model\Object;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Exceptions\DataSheets\DataSheetMapperError;
use exface\Core\Exceptions\DataSheets\DataSheetMapperInvalidInputError;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Interfaces\DataSheets\DataSheetMapperInterface;

class DataSheetMapper implements DataSheetMapperInterface {
    
    use ImportUxonObjectTrait;
    
    private $workbench = null;
    
    private $fromMetaObject = null;
    
    private $toMetaObject = null;
    
    private $ExpressionMaps = [];
    
    public function __construct(Workbench $workbench)
    {
        $this->workbench = $workbench;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\DataSheets\DataSheetMapperInterface::map()
     */
    public function map(DataSheetInterface $fromSheet)
    {
        if (! $this->getFromMetaObject()->is($fromSheet->getMetaObject())){
            throw new DataSheetMapperInvalidInputError($fromSheet, $this, 'Input data sheet based on "' . $fromSheet->getMetaObject()->getAliasWithNamespace() . '" does not match the input object of the mapper "' . $this->getFromMetaObject()->getAliasWithNamespace() . '"!');
        }
        
        $toSheet = DataSheetFactory::createFromObject($this->getToMetaObject());
        
        foreach ($this->getExpressionMaps() as $map){
            $toSheet = $map->map($fromSheet, $toSheet);
        }
        
        return $toSheet;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = new UxonObject();
        // TODO
        return $uxon;
    }
    
    /**
     * 
     * @return Object
     */
    public function getFromMetaObject()
    {
        if (is_null($this->fromMetaObject)){
            // TODO add error code
            throw new DataSheetMapperError($this, 'No from-object defined in data sheet mapper!');
        }
        
        return $this->fromMetaObject;
    }

    /**
     * @param Object $object
     * @return DataSheetMapper
     */
    public function setFromMetaObject(Object $object)
    {
        $this->fromMetaObject = $object;
        return $this;
    }
    
    /**
     * 
     * @param string $alias_with_namespace
     * @return DataSheetMapper
     */
    public function setFromObjectAlias($alias_with_namespace)
    {
        return $this->setFromMetaObject($this->getWorkbench()->model()->getObject($alias_with_namespace));
    }
    
    /**
     * 
     * @return \exface\Core\CommonLogic\Workbench
     */
    public function getWorkbench()
    {
        return $this->workbench;
    }
    
    /**
     * @return Object
     */
    public function getToMetaObject()
    {
        if (is_null($this->toMetaObject)){
            // TODO add error code
            throw new DataSheetMapperError($this, 'No to-object defined in data sheet mapper!');
        }
        return $this->toMetaObject;
    }

    /**
     * @param Object $toMetaObject
     */
    public function setToMetaObject(Object $toMetaObject)
    {
        $this->toMetaObject = $toMetaObject;
        return $this;
    }

    /**
     * @return DataSheetExpressionMap[]
     */
    public function getExpressionMaps()
    {
        return $this->ExpressionMaps;
    }

    /**
     * 
     * @param DataSheetExpressionMap[]|UxonObject[]
     * @return DataSheetMapper
     */
    public function setExpressionMaps(array $expressionMapsOrUxonObjects)
    {
        foreach ($expressionMapsOrUxonObjects as $instance){
            if ($instance instanceof DataSheetExpressionMap){
                $map = $instance;
            } elseif ($instance instanceof UxonObject){
                $map = $this->createExpressionMap();
                $map->importUxonObject($instance);                
            } else {
                throw new DataSheetMapperError($this, 'Invalid format "' . gettype($instance) . '" of expression mapping given: expecting instantiated DataSheetExpressionMap or its UXON description!');
            }
            
            $this->addExpressionMap($map);
        }
        return $this;
    }
    
    /**
     * @return DataSheetExpressionMap
     */
    protected function createExpressionMap()
    {
        return new DataSheetExpressionMap($this);
    }
    
    /**
     * 
     * @param DataSheetExpressionMap $map
     * @return \exface\Core\CommonLogic\DataSheets\DataSheetMapper
     */
    public function addExpressionMap(DataSheetExpressionMap $map)
    {
        $this->ExpressionMaps[] = $map;
        return $this;
    }
    
}