<?php

namespace exface\Core\Layouts;

use exface\Core\Interfaces\Layouts\TextLayoutInterface;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;

/**
 * This layout uses the twig template engine (https://twig.sensiolabs.org/).
 * 
 * Example:
 * 
 * -------------------------------------------------------------------------
 * Invoice {% INOICE__INVOICE_NO|first %} 
 * Date {% INVOICE__INVOICE_DATE|first %}
 * 
 * Description                     Qty          Price           Sum
 * 
 * {% for row in rows %}
 * {{row.DESCR}}                   {{row.QTY}}  {{row.PRICE}}   {{row.SUM}}
 * {% endfor %}
 * 
 * TOTAL                           {%QTY:SUM%}                  {%SUM:SUM%}                         
 * -------------------------------------------------------------------------
 * 
 * filled will the follwoing data sheet
 * 
 *  {
 *      "object_alias": "my.App.INVOICE_POSITION",
 *      "columns": [
 *          {"attribute_alias": "INVOICE__INVOICE_NO"},
 *          {"attribute_alias": "INVOICE__INVOICE_DATE"},
 *          {"attribute_alias": "DESCR"},
 *          {"attribute_alias": "QTY", "footer": "SUM"},
 *          {"attribute_alias": "PRICE"},
 *          {"attribute_alias": "SUM", "footer": "SUM"}
 *      ]
 *  }
 *  
 * will result in something like 
 * 
 * -------------------------------------------------------------------------
 * Invoice 2006/8976535 
 * Date 01.06.2017
 * 
 * Description                     Qty          Price           Sum
 * Product 1                       1            19,99 €         19,99 €
 * Product 3                       2            10,00 €         20,00 €
 * 
 * TOTAL                           3                            39,99 €
 * -------------------------------------------------------------------------
 * 
 * 
 * @author Andrej Kabachnik
 *
 */
class TwigLayout implements TextLayoutInterface {
    
    public function print(DataSheetInterface $dataSheet = null){
        
    }
    
    public function setBody($string){
        
    }
    
    public function getBody($string){
        
    }
    
    public function getHeaderLayout(){
        
    }
    
    public function setHeaderLayout(TextLayoutInterface $layout){
        
    }
    
    public function setHeaderLayoutAlias($alias_with_namespace){
        
    }
    
    public function getHeaderObjectRelationPath(){
        
    }
    
    public function setHeaderObjectRelationPath($relation_path_or_string){
        
    }
    
    public function getFooterLayout(){
        
    }
    
    public function setFooterLayoutAlias($alias_with_namespace){
        
    }
    
    public function setFooterLayout(TextLayoutInterface $layout){
        
    }
    
    public function getFooterObjectRelationPath(){
        
    }
    
    public function setFooterObjectRelationPath($relation_path_or_string){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::fill()
     */
    public function fill(DataSheetInterface $dataSheet){
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Layouts\LayoutInterface::prepareDataSheetToFill()
     */
    public function prepareDataSheetToFill(DataSheetInterface $dataSheet = null){
        
    }
    
}
