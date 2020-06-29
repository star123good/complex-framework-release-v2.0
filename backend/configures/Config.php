<?php

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      configuration
 *
 *      define default config values
 *      define constant and variable with array or class.
 *      read config.json and convert to array from json data.
 *      rename config.json to config.rs in order to protect site.
 *      ex) $string = file_get_contents("config.json");  $json = json_decode($string, true);
 *      for using config, $_GLOBAL['CONFIG']->getConfig('parameter');
 *
**********************************************************************************************/


/*
 *      define config constants
 */
define('VERSION',           '2.0');                                 // Complex Framework Version

define('URI_SPLIT',         '/');                                   // Split of URI, Path
define('HTTP_HTTPS',         'http://');                            // http:// or https://

define("ROOT_PATH",         dirname(__FILE__) . URI_SPLIT);                     // Root path

define('PATH_BACKEND',      'backend'. URI_SPLIT);                              // backend path
define('PATH_CONFIGURES',   PATH_BACKEND . 'configures'. URI_SPLIT);            // configures path
define('PATH_CONTROLLERS',  PATH_BACKEND . 'controllers'. URI_SPLIT);           // controllers path
define('PATH_DEVELOPMENT',  PATH_BACKEND . 'development'. URI_SPLIT);           // development path
define('PATH_HELPS',        PATH_BACKEND . 'helps'. URI_SPLIT);                 // helps path
define('PATH_LIBRARY',      PATH_BACKEND . 'library'. URI_SPLIT);               // library path
define('PATH_MODELS',       PATH_BACKEND . 'models'. URI_SPLIT);                // models path
define('PATH_LANGUAGES',    PATH_BACKEND . 'languages'. URI_SPLIT);             // languages path
define('PATH_FRONTEND',     'frontend'. URI_SPLIT);                             // frontend path
define('PATH_PUBLIC',       PATH_FRONTEND . 'public'. URI_SPLIT);               // public path
define('PATH_VIEWS',        PATH_FRONTEND . 'views'. URI_SPLIT);                // views path
define('PATH_VIEW_COMPONENTS',  PATH_VIEWS . 'components'. URI_SPLIT);          // components path
define('PATH_VIEW_LAYOUTS',     PATH_VIEWS . 'layouts'. URI_SPLIT);             // layouts path
define('PATH_VIEW_PAGES',       PATH_VIEWS . 'pages'. URI_SPLIT);               // pages path

define('CHARSET',           'utf8');                                // database charset
define('TABLE_PREFIX',      'complex_');                            // table prefix
define('TABLE_SUFFIX',      '_table');                              // table suffix

define('TOKEN_EXP_DATE_LIMIT',  24 * 3600);                         // token expire date time limit (unit is seconds)


/*
 *      Config Class
 */
class Config{
    
    private $datas,                                     // config Data values
            $configFilePath;                            // Config json File Path

            
    public function __construct()
    {
        $this->datas = array();

        // read
        $this->configFilePath = PATH_CONFIGURES . "config.json";
        $this->readConfigJSON();

        // web & public path
        $this->setConfig('WEB_PATH', HTTP_HTTPS . $this->getConfig('WEB_HOST') . URI_SPLIT);
        $this->setConfig('PUBLIC_PATH', $this->getConfig('WEB_PATH') . "public" . URI_SPLIT);
        $this->setConfig('ASSETS_PATH', $this->getConfig('PUBLIC_PATH') . "assets" . URI_SPLIT);
        $this->setConfig('CSS_PATH', $this->getConfig('PUBLIC_PATH') . "css" . URI_SPLIT);
        $this->setConfig('SCSS_PATH', $this->getConfig('PUBLIC_PATH') . "scss" . URI_SPLIT);
        $this->setConfig('FONTS_PATH', $this->getConfig('PUBLIC_PATH') . "fonts" . URI_SPLIT);
        $this->setConfig('IMAGES_PATH', $this->getConfig('PUBLIC_PATH') . "images" . URI_SPLIT);
        $this->setConfig('JS_PATH', $this->getConfig('PUBLIC_PATH') . "js" . URI_SPLIT);
    }

    /**
     *      read config json file
     *      @return  void
     */
    private function readConfigJSON()
    {
        $string = file_get_contents($this->configFilePath);
        $json = json_decode($string, true);
        $this->datas = array_merge($this->datas, $json);
    }

    /**
     *      set config value 
     *      @param  string  $parameterKey
     *      @param  mixed   $parameterValue
     *      @return  void 
     */
    public function setConfig($parameterKey, $parameterValue)
    {
        $this->datas[$parameterKey] = $parameterValue;
    }

    /**
     *      get config value 
     *      @param  string  $parameterKey
     *      @return mixed 
     *      @example    $GLOBALS['CONFIG']->getConfig('KEY_NAME')
     */
    public function getConfig($parameterKey)
    {
        if (isset($this->datas[$parameterKey])) {
            return $this->datas[$parameterKey];
        }
        return null;
    }

}