<?php
namespace exface\Core\Widgets;

use cebe\markdown\GithubMarkdown;

/**
 * Shows markdown contents rendered as HTML.
 *
 * @author Andrej Kabachnik
 *        
 */
class Markdown extends Html
{
    /**
     * @return string $markdown
     */
    public function getMarkdown()
    {
        $md = $this->getValue();
        return $md === null ? '' : $md;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Widgets\Html::getHtml()
     */
    public function getHtml(){
        return $this->rebaseRelativeLinks(self::convertMarkdownToHtml($this->getMarkdown()));
    }

    /**
     * 
     * @param string $markdown
     * @return string
     */
    public static function convertMarkdownToHtml(string $markdown) : string
    {
        $parser = new GithubMarkdown();
        return $parser->parse($markdown);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Widgets\Html::getCss()
     */
    public function getCss()
    {
        $css = <<<CSS

img {max-width: 100%}

CSS;
        $css .= parent::getCss();
        return $css;
    }
}