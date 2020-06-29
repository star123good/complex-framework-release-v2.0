<?php

    namespace Library;

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      Response Handler
 *
 *      return two case - view(html) or json
 *      http status - 200, 302, 303, 404, 500, etc
 *      content type - html, json, etc
 *      responseJSON,
 *      set cookie
 *      redirect
 *
**********************************************************************************************/


use Library\View as View;
use Library\Log as Log;


/*
 *      Response Class
 */
class Response {

    protected   $version = '1.1',                       // version of http
                $headers = array(),                     // response header
                $statusCode,                            // status code
                $statusText,                            // status text
                $charset,                               // charset
                $content,                               // content body
                $cookies = array(),                     // cookies
                $view,                                  // View instance
                $flagAPI = false;                       // flag check if response is API

    private static $instance;                           // THE only instance of the class

    public static $statusTexts = array(
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    );


    public function __construct()
    {
    }

    /**
     *      get instance
     *      @return     Response
     *      @example    Response::getInstance()
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
     *      add header
     *      @param  string  $key
     *      @param  string  $value
     *      @return Response
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key][] = $value;
        return $this;
    }

    /**
     *      set header
     *      @param  string  $key
     *      @param  string  $value
     *      @return Response
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = array($value);
        return $this;
    }

    /**
     *      redirect
     *      @param  string  $url
     *      @return void
     */
    public function redirect($url)
    {
        $this->setStatusCode(303)
            ->setHeader('Location', $url)
            ->sendHeaders();

        exit();
    }

    /**
     *      set status code
     *      @param  int     $value
     *      @return Response
     */
    public function setStatusCode($value)
    {
        if (array_key_exists($value, self::$statusTexts)) {
            $this->statusCode = $value;
            $this->statusText = self::$statusTexts[$value];
        }

        return $this;
    }

    /**
     *      set charset
     *      @param  string  $value
     *      @return Response
     */
    public function setCharset($value)
    {
        $this->charset = $value;
        return $this;
    }

    /**
     *      set content
     *      @param  mixed   $value
     *      @return Response
     */
    public function setContent($value)
    {
        // log
        $log = Log::getLog();

        if (is_array($value)) {
            // response as json

            array_walk_recursive($value, function(&$item,$key){
                if ($item instanceof Model) {
                    $item = $item->getArrayData();
                }
            });

            // add log
            if ($log) $value['log'] = $log;
            
            $this->content = json_encode($value);
        }
        else {
            // response as text
            $this->content = (string) $value;

            // add log
            if ($log) $this->content .= $log;
        }
        
        return $this;
    }

    /**
     *      set cookie
     *      @param  string  $key
     *      @param  string  $value
     *      @param  int     $expire
     *      @return Response
     */
    public function setCookie($key, $value, $expire)
    {
        $this->cookies[] = array($key, $value, $expire);
        return $this;
    }

    /**
     *      set flag API
     *      @param  bool    $value
     *      @return Response
     */
    public function setAPI($value=true)
    {
        $this->flagAPI = $value;
        return $this;
    }

    /**
     *      prepare before send
     *      @return Response
     */
    public function prepare()
    {
        // TODO
        // fix some headers such as Content-Type, Content-Length, etc
        if (is_null($this->statusCode)) $this->setStatusCode(200);

        return $this;
    }

    /**
     *      send headers
     *      @return Response
     */
    public function sendHeaders()
    {
        // check if headers already sent
        if (headers_sent()) {
            return $this;
        }

        // headers
        foreach ($this->headers as $name => $values) {
            $replace = 0 === strcasecmp($name, 'Content-Type');
            foreach ($values as $value) {
                header($name.': '.$value, $replace, $this->statusCode);
            }
        }

        // cookies
        foreach ($this->cookies as $cookie) {
            header('Set-Cookie: ' . $cookie[0] . '=' . $cookie[1] . '; expires=' . $cookie[2], false, $this->statusCode);
        }

        // status
        header('HTTP/'. $this->version . ' ' . $this->statusCode . ' ' . $this->statusText, true, $this->statusCode);

        return $this;
    }

    /**
     *      send content
     *      @return Response
     */
    public function sendContent()
    {
        echo $this->content;
        return $this;
    }

    /**
     *      send response
     *      @return void
     */
    public function send()
    {
        if ($this->flagAPI) {
            // json
            $this->json();
        }
        else {
            // html
            $this->html();
        }
    }

    /**
     *      html response
     *      @return Response
     */
    public function html()
    {
        $this->setCharset('utf-8')
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->prepare()
            ->sendHeaders();

        $this->view = View::getInstance();

        // check error from status code
        $flagErrorExist = ($this->statusCode >= 300);
        if ($flagErrorExist) {
            $this->view->render(true, $this->statusCode . " " . $this->statusText);
        }
        else {
            $this->view->render();
        }
        
        return $this;
    }

    /**
     *      json response
     *      @return Response
     */
    public function json()
    {
        $this->setCharset('utf-8')
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->prepare()
            ->sendHeaders()
            ->sendContent();

        return $this;
    }

}