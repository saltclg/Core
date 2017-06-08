<?php

namespace exface\Core\Layouts;

use exface\Core\CommonLogic\Model\Object;
use exface\Core\Interfaces\Layouts\LayoutInterface;
use exface\Core\CommonLogic\Traits\ImportUxonObjectTrait;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Interfaces\NameResolverInterface;

abstract class AbstractLayout implements LayoutInterface {
    
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
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::getName()
     */
    public function getName(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::setName()
     */
    public function setName($string){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::getDescription()
     */
    public function getDescription(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::setDescriptions()
     */
    public function setDescriptions($string){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::setMetaObject()
     */
    public function setMetaObject(Object $object){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::getFillData()
     */
    public function getFillData(){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::getApp()
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
