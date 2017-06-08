<?php
namespace exface\Core\Interfaces\Layouts;

/**
 * Paged layouts should be used to generated page-based documents like DOCX.
 * 
 * In addition to the functionality of regular text layouts they support
 * various features related to pages: page numbering, adding headers and
 * footers to pages, etc.
 * 
 * @author Andrej Kabachnik
 *
 */
interface PagedTextLayoutInterface extends TextLayoutInterface
{    
    /**
     * @return boolean
     */
    public function getPageNumbering();
    
    /**
     * 
     * @param boolean $true_or_false
     * @return PagedTextLayoutInterface
     */
    public function setPageNumbering($true_or_false);
    
    public function getTitlePageLayout();
    
    public function setTitlePageLayout(LayoutInterface $layout);
}