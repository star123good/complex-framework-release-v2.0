<?php

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      auto load
 *      main core of Complex framework
 *
 *      include required library, help, controller, model, view
 *      if file exist, include_once or require_once
 *      create each object from required class
 *      using Globals class with static variables such as controller instance, view instance,
 *      request instance, response instance, session instance, database instance, log class, etc.
 *
**********************************************************************************************/


use Library\Route as Route;
use Library\Request as Request;
use Library\Response as Response;
use Library\Log as Log;


/*
 *      Complex Class 
 */
class Complex {
    
    public 	$router,                      		// Route Class Instance
    		$controller,                      	// Controller Class Instance
    		$request,                         	// Request Class Instance
    		$response;                        	// Response Class Instance
	
	private static 	$flagDevelop;				// environment is development
			

	public function __construct()
	{
	}

	/**
	 * 		initialize
	 * 		create some instances
	 */
	private function init()
	{
		// create new request
		$this->request = Request::getInstance();

		// create new response
		$this->response = Response::getInstance();

		// get router from global router
		$this->router = Route::getInstance();
		
		// create new command
		if ($this->request->isCLI()) {
		}

		// set env
		$this->setEnv();
	}
	
	/**
	 * 		get file name from class name
	 * 		@param	string	$className
	 * 		@return string
	 */
	private static function getFileName($className)
	{
		// explode classname to filepaths array
		$filepaths = explode("\\", $className);
		$last = count($filepaths) - 1;

		// get full path from filepaths array
		$fullpath = PATH_BACKEND;
		for($i = 0; $i < $last; $i ++) {
			$fullpath .= strtolower($filepaths[$i]) . "/";
		}
		$fullpath .= $filepaths[$last] . ".php";
		
		return $fullpath;
	}

	/**
	 * 		auto load
	 */
    public static function autoload($className)
	{
		$filename = Complex::getFileName($className);

        // require once
		if (file_exists($filename)) {
			require_once($filename);
		}
	}

	/**
	 * 		exception handler
	 * 		@param	Exception	$exception
	 * 		@return void
	 */
    public static function exceptionHandler($exception)
	{
		if (static::$flagDevelop) echo " | Exception: " . $exception->getMessage() . PHP_EOL;
	}

	/**
	 * 		error handler
	 * 		@param	int		$errorLevel
	 * 		@param	string	$errorMessage
	 * 		@param	string	$errorFile
	 * 		@param	int		$errorLine
	 * 		@param	array	$errorContext
	 * 		@return void
	 */
    public static function errorHandler($errorLevel, $errorMessage, $errorFile, $errorLine, $errorContext)
	{
		if (static::$flagDevelop) echo " | Error: [$errorLevel] $errorMessage - $errorFile:$errorLine " . PHP_EOL;
	}

	/**
	 * 		set environment
	 */
	private function setEnv()
	{
		switch ($GLOBALS['CONFIG']->getConfig('ENV')) {
			case "production" :
				static::$flagDevelop = false;
				break;
			case "development" :
			default :
				static::$flagDevelop = true;
				break;
		}
	}
	
	/**
	 * 		register
	 * 		calls some of ini_set()
	 */
	public function register()
	{
		// error report according to environment
		if (static::$flagDevelop) error_reporting(E_ALL);
		else error_reporting(0);

		// autoload register
		spl_autoload_register('Complex::autoload');

		// set exception handler
		set_exception_handler('Complex::exceptionHandler');

		// set error handler
		set_error_handler('Complex::errorHandler');
	}

	/**
	 * 		run
	 */
	public function run()
	{
		// init
		$this->init();

		// check from browser or cli
		if ($this->request->isCLI()) {
			Log::addLog("cli running...");
		}
		else {
			Log::addLog("browser running...");
			// set URI to router from request URI
			$this->router->setRequest($this->request->getPath(), $this->request->getMethod());

			// get controller & method
			$controllerName = $this->router->getController();
			$methodName = $this->router->getMethod();
			$middlewareControllerName = $this->router->getController(true);
			$middlewareMethodName = $this->router->getMethod(true);

			$flagNotFound = true;
			$flagCheckMiddleware = true;

			// middleware
			if ($middlewareControllerName && class_exists($middlewareControllerName)) {
				$middlewareController = new $middlewareControllerName;
				if ($middlewareMethodName && method_exists($middlewareControllerName, $middlewareMethodName)) {
					$flagCheckMiddleware = $middlewareController->$middlewareMethodName();

					Log::addLog(" middleware controller : " . $middlewareControllerName 
						. ", method : " . $middlewareMethodName . ", result is : " . $flagCheckMiddleware);
				}
			}

			// controller
			if ($flagCheckMiddleware && $controllerName && class_exists($controllerName)) {
				$this->controller = new $controllerName;
				// method
				if ($methodName && method_exists($controllerName, $methodName)) {
					// call function
					call_user_func_array(array($this->controller, $methodName), $this->router->getParameters());

					$flagNotFound = false;
					
					Log::addLog("controller : " . $controllerName . ", method : " . $methodName);
				}
			}
		}

		if (!$flagCheckMiddleware) {
			// middleware checked false
			$this->response->setStatusCode(500);
			Log::addLog("Middleware Failed");
		}
		else if ($flagNotFound) {
			// not found controller or method
			$this->response->setStatusCode(404);
			Log::addLog("Controller and Method Not Found");
		}

		// show logs in the case of development
		if (static::$flagDevelop) Log::setVisible();

		// reponse
		$this->response->send();
	}

}