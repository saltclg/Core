<?php
namespace exface\Core\CommonLogic\Model;

use exface\Core\Interfaces\Model\AggregatorInterface;
use exface\Core\CommonLogic\Constants\AggregatorFunctions;

class Aggregator implements AggregatorInterface {
    
    private $aggregator_string = null;
    
    private $function = null;
    
    private $arguments = [];
    
    public function __construct($aggregator_string)
    {
        $aggregator_string = (string) $aggregator_string;
        $this->aggregator_string = $aggregator_string;
        $this->importString($aggregator_string);
    }
    
    public function getFunction()
    {
        return $this->function;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function hasArguments()
    {
        return empty($this->arguments) ? false : true;
    }
    
    public function exportString()
    {
        return $this->getFunction() . ($this->hasArguments() ? '(' . implode(', ', $this->getArguments()) . ')' : '');
    }

    public function importString($aggregator_string)
    {
        if ($args_pos = strpos($aggregator_string, '(')) {
            $this->function = new AggregatorFunctions(strtoupper(substr($aggregator_string, 0, $args_pos)));
            $this->arguments = explode(',', substr($aggregator_string, ($args_pos + 1), - 1));
            $this->arguments = array_map('trim', $this->arguments);
        } else {
            $this->function = new AggregatorFunctions($aggregator_string);
        }
        return $this;
    }
    
    public function __toString()
    {
        return $this->exportString();
    }
}