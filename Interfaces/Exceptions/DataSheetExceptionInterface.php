<?php
namespace exface\Core\Exceptions\DataSources;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;

Interface DataSheetExceptionInterface {
	
	/**
	 * 
	 * @param DataSheetInterface $data_sheet
	 * @param string $message
	 * @param string $code
	 * @param \Throwable $previous
	 */
	public function __construct (DataSheetInterface $data_sheet, $message, $code, $previous = null);
	
	/**
	 * 
	 * @return DataSheetInterface
	 */
	public function get_data_sheet();
	
	/**
	 * 
	 * @param DataSheetInterface $sheet
	 * @return DataSheetExceptionInterface
	 */
	public function set_data_sheet(DataSheetInterface $sheet);
	
}
?>