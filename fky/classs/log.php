<?php 
namespace fky\classs;
require __DIR__.'/../inc/wechat/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Formatter\JsonFormatter;
// class Log extends Logger{
// 	private $fkyhandler;
// 	function __construct($name = 'fky', array $handlers = array(), array $processors = array(), $path = __DIR__.'/../../data/log/fky.log'){
// 		$this->fkyhandler = new StreamHandler($path, Logger::DEBUG);
// 		array_unshift($handlers, $this->fkyhandler);
// 		parent::__construct($name,$handlers,$processors);
// 	}
// }

class Log 
{
	private $fkyhandler = null;

    private static $_instance = null;

    private $_logger = null;

    // $handlers   [日志管理器]
    private $_handlers = array();
    //$processors [日志处理器,在日志后添加处理信息]
    private $_processors = array();

    /**
     * [__construct description]
     * @param string $name       [日志名称]
     * @param [type] $path       [路径]
     */
	function __construct($name = 'fky', $path = __DIR__.'/../../data/log/fky.log'){
		$stream_handler = new StreamHandler($path, Logger::DEBUG);
		// $stream_handler->setFormatter(new JsonFormatter());//格式化成json
		array_unshift($this->_handlers, $stream_handler);//加入handler 日志管理器数组，配置管理器
		
		array_unshift($this->_processors, new WebProcessor);//请求来源的信息
		array_unshift($this->_processors, new IntrospectionProcessor);//当前打印日志的文件信息
		//在日的后面加上了uid和process_id
		// array_unshift($this->_processors, new ProcessIdProcessor);
		array_unshift($this->_processors, new UidProcessor(16));//加入processors 日志管理器数组，配置管理器
		// array_unshift($this->_processors, new PsrLogMessageProcessor);//PSR-3规则处理信息

		if (null === $this->_logger) {
            $this->_logger = new Logger($name, $this->_handlers, $this->_processors);
        }
		 
	}

	 /**
     * @return string
     */
    public function getName()
    {
        return $this->_logger->getName();
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addDebug($message, array $context = array())
    {
        return $this->_logger->addDebug($message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addInfo($message, array $context = array())
    {
        return $this->_logger->addInfo($message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addNotice($message, array $context = array())
    {
        return $this->_logger->addNotice($message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addWarning($message, array $context = array())
    {
        return $this->_logger->addWarning($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addError($message, array $context = array())
    {
        return $this->_logger->addError($message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addCritical($message, array $context = array())
    {
        return $this->_logger->addCritical($message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addAlert($message, array $context = array())
    {
        return $this->_logger->addAlert($message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addEmergency($message, array $context = array())
    {
        return $this->_logger->addEmergency($message, $context);
    }

    /**
     * Gets all supported logging levels.
     *
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevels()
    {
        return $this->_logger->getLevels();
    }

    /**
     * Gets the name of the logging level.
     *
     * @param  int    $level
     * @return string
     */
    public static function getLevelName($level)
    {
        return $this->_logger->getLevelName($level);
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = array())
    {
        return $this->_logger->log($level, $message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = array())
    {
        return $this->_logger->debug($message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $context = array())
    {
    	$msg = $message;
    	if (is_object($message) || is_array($message)) {
        	$msg = var_export($message, true);
        }

        //获取输入日志的上下文环境
        $bt = debug_backtrace(0, 3);
        
        $msg .= " ["; //附加调试参数
        if (count($bt) == 3) {
            $callContext = $bt[1];

            $newCategory = $callContext['class'];
            $newCategory .= '::' . $callContext['function'];
//            if (isset($callContext['line'])) {
//                $newCategory .= '-' . $callContext['line'];
//            }
            $msg .= "class={$newCategory}";
            
            //附加函数参数
            $args = $callContext['args'];
            if  (!empty($args)) {
                $args = json_encode($args);
                $msg .= " args={$args}";
            }
        }
        $msg .= ']';
        return $this->_logger->info($msg, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice($message, array $context = array())
    {
        return $this->_logger->notice($message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warn($message, array $context = array())
    {
        return $this->_logger->warn($message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning($message, array $context = array())
    {
        return $this->_logger->warning($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function err($message, array $context = array())
    {
        return $this->_logger->err($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function error($message, array $context = array())
    {
        return $this->_logger->error($message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function crit($message, array $context = array())
    {
        return $this->_logger->crit($message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical($message, array $context = array())
    {
        return $this->_logger->critical($message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert($message, array $context = array())
    {
        return $this->_logger->alert($message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emerg($message, array $context = array())
    {
        return $this->_logger->emerg($message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency($message, array $context = array())
    {
        return $this->_logger->emergency($message, $context);
    }
}