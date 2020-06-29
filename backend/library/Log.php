<?php

    namespace Library;

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      Log Handler
 *
 *      add & show all logs
 *
**********************************************************************************************/


/*
 *      Log Class
 */
class Log {

    public static   $logs = array(),
                    $flagVisible = false;

    public function __construct()
    {
    }

    /**
     *      add log
     *      @param  string|array  $log
     *      @return void
     */
    public static function addLog($log)
    {
        if (!is_null($log)) {
            if (is_array($log) && !empty($log)){
                static::$logs[] = json_encode($log);
            }
            else if ($log != "") {
                static::$logs[] = $log;
            }
        }
    }

    /**
     *      set visible to show log
     *      @return void
     */
    public static function setVisible()
    {
        static::$flagVisible = true;
    }

    /**
     *      get log
     *      @return string|null
     */
    public static function getLog()
    {
        if (static::$flagVisible) {
            $result = "";
            foreach (static::$logs as $index => $log) {
                $result .= " | LOG_" . $index . ": " . $log . PHP_EOL;
            }
        }
        else {
            $result = null;
        }
        return $result;
    }

}