<?php
namespace exface\Core\Interfaces\Layouters;

use exface\Core\Interfaces\iCanBeConvertedToString;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

/**
 * Text layouts can contain any type of textual markup: XML, JSON, plain text, etc.
 * 
 * They consist of a a body (text) and optionaly a header and a footer (sub-
 * layouts) that are placed at the beginning of the resulting document and at
 * it's end respectively.
 * 
 * The syntax of the body depends on the specific implementation and the
 * templating engine used.
 * 
 * There are many usecases for text layouts: printing documents, templating 
 * email bodies, showing custom widgets, creating reports, exporting data in
 * XML/JSON formats, etc. These usecases typically involve an action (or
 * widget), that take a layout alias as parameter and make sure it is filled
 * out and the result is used somehow. It is up to the action to define the
 * specific output format (printing, email, download, etc.) - the layout takes
 * care of filling the body with data and give it back for further processing.
 * 
 * Since most of the actions and widgets using text layouts will need a specific
 * markup (e.g. a widget will need to know, whether it is going to display
 * HTML or JSON), separate layout implementations should be created for every
 * type of markup. Apart from being easier to understand for the user, this will
 * also allow decent type validation in the processing code.
 * 
 * @author Andrej Kabachnik
 *
 */
interface TextLayouterInterface extends LayouterInterface, iCanBeConvertedToString
{
    /**
     * Returns the filled out layout as a string.
     * 
     * This is a shortcut to calling $layout->fill($ds)->toString().
     * 
     * If no data sheet is given, the sheet last filled in will be used.
     * 
     * @param DataSheetInterface $dataSheet
     * @return string
     */
    public function print(DataSheetInterface $dataSheet = null);
    
    public function setBody($string);
    
    public function getBody($string);
    
    public function getHeaderLayout();
    
    public function setHeaderLayout(TextLayouterInterface $layout);
    
    public function setHeaderLayoutAlias($alias_with_namespace);
    
    public function getHeaderObjectRelationPath();
    
    public function setHeaderObjectRelationPath($relation_path_or_string);
    
    public function getFooterLayout();
    
    public function setFooterLayoutAlias($alias_with_namespace);
    
    public function setFooterLayout(TextLayouterInterface $layout);
    
    public function getFooterObjectRelationPath();
    
    public function setFooterObjectRelationPath($relation_path_or_string);
}