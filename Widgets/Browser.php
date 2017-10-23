<?php
namespace exface\Core\Widgets;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\CommonLogic\UxonObject;

/**
 * Shows a URL or an action in an embedded web browser (e.g. an iFrame in HTML-templates).
 *
 * @author Andrej Kabachnik
 *        
 */
class Browser extends AbstractWidget
{
    private $url = null;
    
    private $action = null;
    
    /**
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Browser
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    /**
     * @return ActionInterface
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param UxonObject|ActionInterface $action
     * @return Browser
     */
    public function setAction($uxon_or_action)
    {
        $this->action = $uxon_or_action;
        return $this;
    }

}
?>