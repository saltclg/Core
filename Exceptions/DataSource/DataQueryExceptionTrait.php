<?php
namespace exface\Core\Exceptions\DataSource;

use exface\Core\Interfaces\DataSources\DataQueryInterface;

trait DataQueryExceptionTrait {
	
	private $query = null;
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\Exceptions\DataConnectorExceptionInterface::get_query()
	 */
	public function get_query(){
		return $this->query;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\Exceptions\DataConnectorExceptionInterface::set_query()
	 */
	public function set_query(DataQueryInterface $query){
		$this->query = $query;
		return $this;
	}
	
}
?>