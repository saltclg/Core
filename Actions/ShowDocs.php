<?php
namespace exface\Core\Actions;

use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\Filemanager;
use cebe\markdown\GithubMarkdown;
use exface\Core\Exceptions\Actions\ActionRuntimeError;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Factories\DataSheetFactory;

/**
 * Renderes a widget showing a file or folder from the internal documentation.
 * 
 * @author Andrej Kabachnik
 *        
 */
class ShowDocs extends ShowWidget
{
    private $path = null;
    
    private $docs_widget = null;
    
    private $wrapInHtml = true;
    
    /**
     * @return string
     */
    public function getPath()
    {
        if (is_null($this->path)) {
            if ($url_param = $this->getWorkbench()->getRequestParam('path')) {
                $this->path = $url_param;
            }
        }
        
        return $this->path;
    }
    
    public function getPathAbsolute()
    {
        return Filemanager::pathJoin([$this->getWorkbench()->filemanager()->getPathToVendorFolder(), $this->getPath()]);
    }

    /**
     * @param string $path
     */
    public function setPath($path_relative_to_vendor_folder)
    {
        $this->path = $path_relative_to_vendor_folder;
        return $this;
    }
    
    protected function buildHtmlFromMarkdownFile($absolute_path)
    {
        if (! file_exists($absolute_path)) {
            throw new ActionRuntimeError($this, 'Markdown-file "' . $absolute_path . '" not found!');
        }
        
        $markdown = file_get_contents($absolute_path);
        $parser = new GithubMarkdown();
        $html = $parser->parse($markdown);
        
        return $html;
    }
    
    public function getResultOutput()
    {
        $output = parent::getResultOutput();
        $output = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        {$this->getTemplate()->drawHeaders($this->getResult())}
    </head>
    <body>
        {$output}
    </body>
</html>

HTML;
        return $output;
    }
    
    public function getWidget()
    {
        if (is_nulL($this->docs_widget)) {
            if (is_null($this->getPath())) {
                $this->docs_widget = $this->createWidgetForFolder();
            } elseif (is_dir($this->getPathAbsolute())) {
                $this->docs_widget = $this->createWidgetForFolder($this->getPathAbsolute());
            } elseif (file_exists($this->getPathAbsolute())) {
                $this->docs_widget = $this->createWidgetForFile($this->getPathAbsolute());
            } else {
                throw new ActionRuntimeError($this, 'Documentation file "' . $this->getPath() . '" not found!');
            }
        }
        return $this->docs_widget;
    }
    
    protected function createWidgetForFolder($absolute_path = null)
    {
        $uxon = new UxonObject([
            "widget_type" => "DataTable",
            "object_alias" => "exface.Core.Docs",
            "lazy_loading" => false,
            "filters" => [
                [
                    "attribute_alias" => "NAME",
                    "width" => "max"
                ],
                [
                    "attribute_alias" => "PATH_RELATIVE",
                    "width" => "max",
                    "disabled" => true
                ]
            ],
            "columns" => [
                [
                    "attribute_alias" => "NAME"
                ],
                [
                    "attribute_alias" => "PATHNAME_RELATIVE",
                    "hidden" => false
                ]
            ],
            "buttons" => [
                [
                    "action_alias" => "exface.Core.GoToUrl",
                    "action_url" => "exface/exface.php?exftpl=exface.JEasyUiTemplate&resource=396&action=exface.Core.ShowDocs&path=[#PATHNAME_RELATIVE#]"
                ]
            ]
        ]);
        $widget = WidgetFactory::createFromUxon($this->getCalledOnUiPage(), $uxon, $this->getCalledByWidget());
        
        if (! is_null($absolute_path)) {
            $prefill_data = DataSheetFactory::createFromObject($widget->getMetaObject());
            $prefill_data->addFilterFromString('PATHNAME_RELATIVE', $this->getPath(), EXF_COMPARATOR_IS);
            $widget->prefill($prefill_data);
        }
        
        return $widget;
    }
    
    protected function createWidgetForFile($absolute_path)
    {
        /* @var $widget \exface\Core\Widgets\Markdown */
        $widget = WidgetFactory::create($this->getCalledOnUiPage(), 'Markdown', $this->getCalledByWidget());
        $widget->setHtml($this->buildHtmlFromMarkdownFile($absolute_path));
        
        return $widget;
    }
    /**
     * @return boolean
     */
    public function getWrapInHtml()
    {
        return $this->wrapInHtml;
    }

    /**
     * @param boolean $wrapInHtml
     */
    public function setWrapInHtml($true_or_false)
    {
        $this->wrapInHtml = BooleanDataType::cast($true_or_false);
        return $this;
    }

}
?>