<?php

    namespace Library;

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      Route Class
 *
 *      METHOD - GET, POST, PUT, DELETE
 *      URI - object/method/id, etc
 *      middleware
 *
**********************************************************************************************/


use Library\Log as Log;


/**
 *      Route Class
 */
class Route {

    const   METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTION'),         // request methods
            SEPERATOR = '@',                                                            // action sperator
            FLAG_TO_LOWER = true;                                                       // if uri is ignored lowercase or uppercase

    private $routes = array(),                  // routes array
            $controller = null,                 // controller name
            $method = null,                     // method name
            $middlewareController = null,       // middleware controller name
            $middlewareMethod = null,           // middleware method name
            $parameters = array();              // parameters array

    private static $instance;                   // THE only instance of the class


    public function __construct()
    {
    }

    /**
     *      get instance
     *      @return     Route
     *      @example    Route::getInstance()
     */
    public static function getInstance()
    {
        if ( !isset(self::$instance))
        {
            self::$instance = new self;
        }
       
        return self::$instance;
    }

    /**
     *      get method
     *      @param  string  $uri
     *      @param  string  $action
     *      @param  string  $middleware     default : ""
     *      @return void
     *      @example    $router->get('path_to/active/{params1}/{params2}', 'Controller@methods', 'MiddlewareController@MiddlewareMethod')
     */
    public function get($uri, $action, $middleware="")
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     *      post method
     *      @param  string  $uri
     *      @param  string  $action
     *      @param  string  $middleware     default : ""
     *      @return void
     */
    public function post($uri, $action, $middleware="")
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     *      put method
     *      @param  string  $uri
     *      @param  string  $action
     *      @param  string  $middleware     default : ""
     *      @return void
     */
    public function put($uri, $action, $middleware="")
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    /**
     *      delete method
     *      @param  string  $uri
     *      @param  string  $action
     *      @param  string  $middleware     default : ""
     *      @return void
     */
    public function delete($uri, $action, $middleware="")
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     *      Restful
     *      @param  string  $uri
     *      @param  string  $controller
     *      @param  string  $middleware     default : ""
     *      @return void
     */
    public function rest($uri, $controller, $middleware="")
    {
        $this->get($uri,                                            $controller . self::SEPERATOR . "index", $middleware);
        $this->get($uri . URI_SPLIT . "new",                        $controller . self::SEPERATOR . "new", $middleware);
        $this->get($uri . URI_SPLIT . "edit" . URI_SPLIT . "{id}",  $controller . self::SEPERATOR . "edit", $middleware);
        $this->get($uri . URI_SPLIT . "{id}",                       $controller . self::SEPERATOR . "show", $middleware);
        $this->post($uri,                                           $controller . self::SEPERATOR . "insert", $middleware);
        $this->put($uri . URI_SPLIT . "{id}",                       $controller . self::SEPERATOR . "update", $middleware);
        $this->delete($uri . URI_SPLIT . "{id}",                    $controller . self::SEPERATOR . "remove", $middleware);
    }

    /**
     *      add route
     *      @param  string  $type
     *      @param  string  $uri
     *      @param  string  $action
     *      @param  string  $middleware
     *      @return void
     */
    private function addRoute($type, $uri, $action, $middleware)
    {
        if (in_array($type, self::METHODS)) {
            // check action has '@'
            if (substr_count($action, self::SEPERATOR) == 1) {
                // action
                list($actionController, $actionMethod) = explode(self::SEPERATOR, $action);

                // check middleware
                if (substr_count($middleware, self::SEPERATOR) == 1) {
                    list($middlewareController, $middlewareMethod) = explode(self::SEPERATOR, $middleware);
                }
                else {
                    $middlewareController = null;
                    $middlewareMethod = null;
                }

                // uri
                list($uriPattern, $actionParameters) = $this->processURI($uri);

                // add routes list
                $this->routes[] = array(
                    'TYPE' => $type,
                    'URI' => $uriPattern,
                    'CONTROLLER' => $actionController,
                    'METHOD' => $actionMethod,
                    'PARAMETERS' => $actionParameters,
                    'MIDDLEWARE_CONTROLLER' => $middlewareController,
                    'MIDDLEWARE_METHOD' => $middlewareMethod,
                );
            }
        }
    }

    /**
     *      process from uri
     *      @param  string  $uri
     *      @return array
     */
    private function processURI($uri)
    {
        $resURI = "";
        $resParam = array();

        $temp = explode(URI_SPLIT, $uri);
        foreach ($temp as $tempPattern) {
            $tempPattern = trim($tempPattern);
            if ($tempPattern == "") continue;

            if (strlen($tempPattern) > 2 && $tempPattern == "{".substr($tempPattern, 1, -1)."}") {
                $resParam[] = substr($tempPattern, 1, -1);
            }
            else {
                $resURI .= URI_SPLIT . $tempPattern;
            }
        }

        if ($resURI == "") $resURI = URI_SPLIT;
        else if (self::FLAG_TO_LOWER) $resURI = strtolower($resURI) . URI_SPLIT;

        return array($resURI, $resParam);
    }

    /**
     *      set URI & Type
     *      @param  string  $uri
     *      @return void
     */
    public function setRequest($uri, $type)
    {
        $flagSearch = false;

        if (self::FLAG_TO_LOWER) $uri = strtolower($uri);
        if (substr($uri, -1) != URI_SPLIT) $uri = $uri . URI_SPLIT;

        if (!empty($this->routes)) {
            foreach ($this->routes as $route) {
                // Method Type && URI
                if ($type == $route['TYPE'] && strpos($uri, $route['URI']) === 0) {
                    $temp = array();

                    // Parameters
                    if (empty($route['PARAMETERS'])) {
                        if ($uri == $route['URI']) $flagSearch = true;
                    }
                    else {
                        $temp = array_filter(explode(URI_SPLIT, $uri), function($u){ return $u != ""; });
                        $tempRoute = array_filter(explode(URI_SPLIT, $route['URI']), function($u){ return $u != ""; });
                        if (count($temp) == (count($route['PARAMETERS']) + count($tempRoute))) $flagSearch = true;
                    }

                    // if matching
                    if ($flagSearch) {
                        // controller
                        $this->controller = $route['CONTROLLER'];
                        // method
                        $this->method = $route['METHOD'];

                        // parameters
                        if (empty($temp)) {
                            $this->parameters = array();
                        }
                        else {
                            $temp = array_slice($temp, count($tempRoute));
                            $this->parameters = array_combine($route['PARAMETERS'], $temp);
                        }

                        // middleware
                        $this->middlewareController = $route['MIDDLEWARE_CONTROLLER'];
                        $this->middlewareMethod = $route['MIDDLEWARE_METHOD'];
                        break;
                    }
                }
            }
        }
    }

    /**
     *      get controller name
     *      @param  bool    $isMiddleware
     *      @return string
     */
    public function getController($isMiddleware=false)
    {
        if ($isMiddleware) {
            // middleware controller
            if ($this->middlewareController) $this->middlewareController = "Controllers\\" . $this->middlewareController . "Controller";
            return $this->middlewareController;
        }
        else {
            // controller
            if ($this->controller) $this->controller = "Controllers\\" . $this->controller . "Controller";
            return $this->controller;
        }
    }

    /**
     *      get method name
     *      @param  bool    $isMiddleware
     *      @return string
     */
    public function getMethod($isMiddleware=false)
    {
        if ($isMiddleware) {
            // middleware method
            return $this->middlewareMethod;
        }
        else {
            // method
            return $this->method;
        }
    }

    /**
     *      get parameters array
     *      @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

}