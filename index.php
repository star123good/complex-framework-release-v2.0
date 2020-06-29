<?php

/**********************************************************************************************
 *
 *      index
 *
 *      define CORRECT_PATH
 *      include autoload
 *
**********************************************************************************************/

define('CORRECT_PATH', "COMPLEX_CORRECT_PATH");
define('CONFIG_FILEPATH', "backend/configures/Config.php");


// require config php
require_once(CONFIG_FILEPATH);
// create config object
$CONFIG = new Config();

// require common help php
require_once(PATH_HELPS . 'common.help.php');

// require autoload php
require_once(PATH_BACKEND . 'Complex.php');
// register Complex framework
$APP = new Complex();
// register app
$GLOBALS['APP']->register();

// require router php
require_once(PATH_BACKEND . 'router.php');

// run app
$GLOBALS['APP']->run();