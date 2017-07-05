<?php
namespace exface\Core\CommonLogic\Contexts;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\Contexts\ContextInterface;
use exface\Core\Interfaces\Contexts\ContextScopeInterface;
use exface\Core\Exceptions\Contexts\ContextRuntimeError;
use exface\Core\Widgets\Container;

abstract class AbstractContext implements ContextInterface
{

    private $exface = null;

    private $scope = null;

    private $alias = null;
    
    private $indicator = null;
    
    private $icon = null;
    
    private $name = null;
    
    private $context_bar_visibility = null;
    
    /**
     * 
     * @param \exface\Core\CommonLogic\Workbench $exface
     */
    public function __construct(\exface\Core\CommonLogic\Workbench $exface)
    {
        $this->exface = $exface;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getScope()
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setScope()
     */
    public function setScope(ContextScopeInterface $context_scope)
    {
        $this->scope = $context_scope;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getDefaultScope()
     */
    public function getDefaultScope()
    {
        return $this->getWorkbench()->context()->getScopeWindow();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\ExfaceClassInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->exface;
    }

    /**
     * Returns a serializable UXON object, that represents the current contxt, 
     * thus preparing it to be saved in a session, cookie, database or whatever 
     * is used by a context scope.
     * 
     * What exactly ist to be saved, strongly depends on the context type: an 
     * action context needs an acton alias and, perhaps, a data backup, a filter 
     * context needs to save it's filters conditions, etc. In any case, the 
     * serialized version should contain enough data to restore the context 
     * completely afterwards, but also not to much data in order not to consume 
     * too much space in whatever stores the respective context scope.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject()
    {
        return $this->getWorkbench()->createUxonObject();
    }

    /**
     * Restores a context from it's UXON representation.
     * 
     * The input is whatever export_uxon_object() produces for this context type.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::importUxonObject()
     * @return ContextInterface
     */
    public function importUxonObject(UxonObject $uxon)
    {
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getAlias()
     */
    public function getAlias()
    {
        if (! $this->alias) {
            $this->alias = substr(get_class($this), (strrpos(get_class($this), "\\") + 1), - 7);
        }
        return $this->alias;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getIndicator()
     */
    public function getIndicator()
    {
        return $this->indicator;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setIndicator()
     */
    public function setIndicator($indicator)
    {
        $this->indicator = $indicator;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getContextBarVisibility()
     */
    public function getContextBarVisibility()
    {
        if (is_null($this->context_bar_visibility)){
            $this->setVisibility(ContextInterface::CONTEXT_BAR_SHOW_IF_NOT_EMPTY);
        }
        return $this->context_bar_visibility;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setContextBarVisibility()
     */
    public function setContextBarVisibility($value)
    {
        $value = mb_strtolower($value);
        if ($value != ContextInterface::CONTEXT_BAR_DISABED 
        && $value != ContextInterface::CONTEXT_BAR_HIDE_ALLWAYS 
        && $value != ContextInterface::CONTEXT_BAR_SHOW_ALLWAYS 
        && $value != ContextInterface::CONTEXT_BAR_SHOW_IF_NOT_EMPTY) {
            throw new ContextRuntimeError($this, 'Invalid context_bar_visibility value "' . $value . '" for context "' . $this->getAlias() . '"!');
            return;
        }
        $this->context_bar_visibility = $value;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::isEmpty()
     */
    public function isEmpty()
    {
        return $this->active;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getContextBarPopup()
     */
    public function getContextBarPopup(Container $container)
    {
        return $container;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getIcon()
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setIcon()
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setName()
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
 
 
}
?>