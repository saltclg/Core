<?php

namespace exface\Core\Layouters;

use exface\Core\CommonLogic\Model\Object;
use exface\Core\Interfaces\Layouters\LayouterInterface;
use exface\Core\CommonLogic\Traits\ImportUxonObjectTrait;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Interfaces\NameResolverInterface;

abstract class AbstractLayouter implements LayouterInterface {
    
    use ImportUxonObjectTrait;
    
    private $workbench = null;
    private $nameResolver = null;
    
    public function __construct(Workbench $workbench, NameResolverInterface $nameResolver){
        $this->workbench = $workbench;
        $this->nameResolver = $nameResolver;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::getName()
     */
    public function getName(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::setName()
     */
    public function setName($string){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::getDescription()
     */
    public function getDescription(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::setDescriptions()
     */
    public function setDescriptions($string){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::setMetaObject()
     */
    public function setMetaObject(Object $object){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::getFillData()
     */
    public function getFillData(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouters\LayouterInterface::getApp()
     */
    public function getApp(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject(){
        
    }
    
    public function getAlias(){
        return $this->getNameResolver()->getAlias();
    }
    
    public function getAliasWithNamespace(){
        return $this->getNameResolver()->getAliasWithNamespace();
    }
    
    public function getNamespace(){
        return $this->getNameResolver()->getNamespace();
    }
    
    /**
     * 
     * @return \exface\Core\Interfaces\NameResolverInterface
     */
    protected function getNameResolver(){
        return $this->nameResolver;
    }
    
    public function getApp(){
        return $this->getWorkbench()->getApp($this->getNameResolver()->getAppAlias());
    }
}
