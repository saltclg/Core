<?php
namespace exface\Core\Interfaces\Layouters;

use exface\Core\Interfaces\ExfaceClassInterface;
use exface\Core\Interfaces\iCanBeConvertedToUxon;
use exface\Core\Interfaces\iUseMetaObject;
use exface\Core\CommonLogic\Model\Object;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\AliasInterface;
use exface\Core\Interfaces\AppInterface;

interface LayouterInterface extends ExfaceClassInterface, iCanBeConvertedToUxon, iUseMetaObject, AliasInterface
{
    /**
     * @return string
     */
    public function getName();
    
    /**
     *
     * @param unknown $string
     * @return LayouterInterface
     */
    public function setName($string);
    
    /**
     * @return string
     */
    public function getDescription();
    
    /**
     *
     * @param unknown $string
     * @return LayouterInterface
     */
    public function setDescriptions($string);

    /**
     * 
     * @param Object $object
     * @return LayouterInterface
     */
    public function setMetaObject(Object $object);
    
    /**
     * Fills the layout with the given data sheet
     * 
     * @param DataSheetInterface $data_sheet
     * @return LayouterInterface
     */
    public function fill(DataSheetInterface $dataSheet);
    
    /**
     * 
     * @param DataSheetInterface $dataSheet
     * @return DataSheetInterface
     */
    public function prepareDataSheetToFill(DataSheetInterface $dataSheet = null);

    /**
     * Returns the data sheet, which was used to fill this layout
     * 
     * @return DataSheetInterface
     */
    public function getFillData();
    
    /**
     * 
     * @return AppInterface
     */
    public function getApp();
    
}