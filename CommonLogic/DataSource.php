<?php
namespace exface\Core\CommonLogic;

use exface\Core\Interfaces\DataSources\DataSourceInterface;
use exface\Core\CommonLogic\Model\Model;
use exface\Core\DataTypes\UxonDataType;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Interfaces\AppInterface;

class DataSource implements DataSourceInterface
{

    private $app;

    private $data_connector;

    private $connection_id;

    private $query_builder;

    private $data_source_id;
    
    private $data_source_name;

    private $connection_config = array();

    private $readable = true;
    
    private $writable = true;
    
    private $workbench = null;
    
    function __construct(AppInterface $app, string $id, string $alias, string $name)
    {
        $this->data_source_id = $id;
        $this->data_source_name = $name;
        $this->app = $app;
        $this->workbench = $app->getWorkbench();
    }

    public function getWorkbench()
    {
        return $this->workbench;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getConnection()
     */
    public function getConnection()
    {
        return $this->getWorkbench()->data()->getDataConnection($this->getId(), $this->getConnectionId());
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getId()
     */
    public function getId()
    {
        return $this->data_source_id;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setId()
     */
    public function setId($value)
    {
        $this->data_source_id = $value;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getDataConnectorAlias()
     */
    public function getDataConnectorAlias()
    {
        return $this->data_connector;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setDataConnectorAlias()
     */
    public function setDataConnectorAlias($value)
    {
        $this->data_connector = $value;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getConnectionId()
     */
    public function getConnectionId()
    {
        return $this->connection_id;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setConnectionId()
     */
    public function setConnectionId($value)
    {
        $this->connection_id = $value;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getQueryBuilderAlias()
     */
    public function getQueryBuilderAlias()
    {
        return $this->query_builder;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setQueryBuilderAlias()
     */
    public function setQueryBuilderAlias($value)
    {
        $this->query_builder = $value;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::getConnectionConfig()
     */
    public function getConnectionConfig()
    {
        return $this->connection_config instanceof UxonObject ? $this->connection_config : new UxonObject();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setConnectionConfig()
     */
    public function setConnectionConfig(UxonObject $value)
    {
        $this->connection_config = $value;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::isReadable()
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setReadable()
     */
    public function setReadable($true_or_false)
    {
        $this->readable = BooleanDataType::cast($true_or_false);
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::isWritable()
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\DataSources\DataSourceInterface::setWritable()
     */
    public function setWritable($true_or_false)
    {
        $this->writable = BooleanDataType::cast($true_or_false);
        return $this;
    }
    
    public function setName(string $readableName) : DataSourceInterface
    {
        $this->data_source_name = $readableName;
        return $this;
    }
    
    public function getName() : string
    {
        return $this->data_source_name;
    }

}
?>