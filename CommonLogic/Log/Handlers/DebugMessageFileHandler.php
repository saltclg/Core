<?php

namespace exface\Core\CommonLogic\Log\Handlers;

use exface\Core\CommonLogic\Log\Formatters\MessageOnlyFormatter;
use exface\Core\CommonLogic\Log\Helpers\LogHelper;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Interfaces\iCanGenerateDebugWidgets;
use exface\Core\Interfaces\Log\LogHandlerInterface;
use exface\Core\Widgets\DebugMessage;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Interfaces\Exceptions\ExceptionInterface;
use exface\Core\CommonLogic\UxonObject;

class DebugMessageFileHandler implements LogHandlerInterface
{

    private $dir;

    private $minLogLevel;

    private $staticFilenamePart;
    
    private $workbench;

    /**
     * DebugMessageFileHandler constructor.
     *
     * @param Workbench $workbench
     * @param string $dir
     * @param string $staticFilenamePart
     * @param string $minLogLevel
     */
    public function __construct(Workbench $workbench, $dir, $staticFilenamePart, $minLogLevel = Logger::DEBUG)
    {
        $this->workbench = $workbench;
        $this->dir                = $dir;
        $this->staticFilenamePart = $staticFilenamePart;
        $this->minLogLevel        = $minLogLevel;
    }

    public function handle($level, $message, array $context = array(), iCanGenerateDebugWidgets $sender = null)
    {
        if (LogHelper::compareLogLevels($level, $this->minLogLevel) < 0) {
            return;
        }
        
        $fileName = $context["id"] . $this->staticFilenamePart;
        // Do no do anything if no file name could be determined or the file exists. The latter
        // case is important as if two loggers would attempt to log to the same file (e.g. the
        // main logger and the debug logger), the file would have double content and it would
        // be impossible to parse it. Since the file name is the id of the debug message, we
        // can be sure that it's content will be the same every time we attempt to write it.
        if (! $fileName || file_exists($this->dir . "/" . $fileName)) {
            return;
        }
        
        $logger  = new \Monolog\Logger("Stacktrace");
        $handler = new StreamHandler($this->dir . "/" . $fileName, $this->minLogLevel);
        $handler->setFormatter(new MessageOnlyFormatter());

        $persistLogLevel = $this->workbench->getConfig()->getOption('LOG.PERSIST_LOG_LEVEL');
        $passthroughLevel = LogHelper::compareLogLevels($level, $this->workbench->getConfig()->getOption('LOG.PASSTHROUGH_LOG_LEVEL')) < 0 ? $level : $this->workbench->getConfig()->getOption('LOG.PASSTHROUGH_LOG_LEVEL');
        
        $fcHandler = new FingersCrossedHandler(
            $handler,
            new ErrorLevelActivationStrategy(Logger::toMonologLevel($persistLogLevel)),
            0,
            true,
            true,
            $passthroughLevel
        );

        $logger->pushHandler($fcHandler);
        
        if ($sender) {
            try {
                $debugWidget     = $sender->createDebugWidget($this->createDebugMessage());
                $debugWidgetData = $debugWidget->exportUxonObject()->toJson(true);
            } catch (\Throwable $e){
                // If errors occur when creating debug widgets, just create an
                // HTML-widget with an exception-dump. If the sender was an error,
                // dump it, otherwise dump the error, that just occured.
                if ($sender instanceof ExceptionInterface){
                    $exception = $sender;
                } else {
                    $exception = $e;
                }
                $debugWidgetData = $this->createHtmlFallback($this->getWorkbench()->getDebugger()->printException($exception, true));
            }
            $logger->log($level, $debugWidgetData);
        } elseif ($context['exception'] instanceof \Throwable){
            // If there is no sender, but the context contains an error, use
            // the error fallback to create a debug widget
            $logger->log($level, $this->createHtmlFallback($this->getWorkbench()->getDebugger()->printException($context['exception'], true)));
        } else {
            // If all the above fails, simply dump the context to the debug widget
            $logger->log($level, $this->createHtmlFallback($this->getWorkbench()->getDebugger()->printVariable($context, true)));
        }
    }
    
    /**
     * Generates a JSON-dump of an HTML-widget with the given contents. 
     * 
     * This is handy if the regular DebugWidget cannot be created for some 
     * reason. Using this fallback it is still possible to create a readable
     * debug widget.
     * 
     * @param string $html
     * @return string
     */
    protected function createHtmlFallback($html){
        $uxon = new UxonObject();
        $uxon->setProperty('widget_type', 'Html');
        $uxon->setProperty('html', $html);
        return $uxon->toJson(true);
    }
    
    /**
     * 
     * @param array $context
     * @return array
     */
    protected function prepareContext($context)
    {
        // do not log the exception in this handler
        if (isset($context["exception"])) {
            unset($context["exception"]);
        }

        return $context;
    }
    
    /**
     * 
     * @return \exface\Core\Widgets\DebugMessage
     */
    protected function createDebugMessage()
    {
        $page = UiPageFactory::createEmpty($this->getWorkbench());

        $debugMessage = new DebugMessage($page);
        $debugMessage->setMetaObject($page->getWorkbench()->model()->getObject('exface.Core.MESSAGE'));

        return $debugMessage;
    }
    
    /**
     * 
     * @return \exface\Core\CommonLogic\Workbench
     */
    public function getWorkbench(){
        return $this->workbench;
    }
}
