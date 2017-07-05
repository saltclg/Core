<?php
namespace exface\Core\Contexts;

use exface\Core\Interfaces\Contexts\ContextScopeInterface;
use exface\Core\Exceptions\Contexts\ContextRuntimeError;

/**
 * 
 *
 * @author Andrej Kabachnik
 *        
 */
class FavoritesContext extends ObjectBasketContext
{

    /**
     * The favorites context resides in the user scope.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Contexts\ObjectBasketContext::getDefaultScope()
     */
    public function getDefaultScope()
    {
        return $this->getWorkbench()->context()->getScopeUser();
    }
    
    public function getScope()
    {
        return $this->getDefaultScope();
    }
    
    public function setScope(ContextScopeInterface $context_scope)
    {
        if ($context_scope != $this->getDefaultScope()){
            throw new ContextRuntimeError($this, 'Cannot use context scope "' . $context_scope->getName() . '" for context "' . $this->getAlias() . '": only user context scope allowed!');
        }
        return parent::setScope($context_scope);
    }
    
    public function getIcon()
    {
        return 'star';
    }
    
    public function getName()
    {
        return $this->getWorkbench()->getCoreApp()->getTranslator()->translate('CONTEXT.FAVORITES.NAME');
    }
    
}
?>