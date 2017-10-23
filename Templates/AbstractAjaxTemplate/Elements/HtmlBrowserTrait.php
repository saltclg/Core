<?php
namespace exface\Core\Templates\AbstractAjaxTemplate\Elements;

use exface\Core\Widgets\Browser;

/**
 * Renders a browser widget as an iFrame
 * 
 * @method Browser getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait HtmlBrowserTrait 
{
    public function generateHtml()
    {
        return <<<HTML

<iframe src="{$this->getWidget()->getUrl()}" style="width: 100%; height: calc(100% - 3px); border: 0;" seamless></iframe>

HTML;
    }
    
    public function generateJs()
    {
        return '';
    }
}