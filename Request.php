<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 10/20/14 7:00 PM
 */

namespace Solve\Http;

use Solve\Storage\ArrayStorage;

/**
 * Class Request
 * @package Solve\Http
 *
 * Class Request represents income request
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class Request {

    const MODE_WEB       = 'WEB';
    const MODE_CONSOLE   = 'CONSOLE';
    const METHOD_POST    = 'POST';
    const METHOD_GET     = 'GET';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_UPDATE  = 'UPDATE';
    const METHOD_OPTIONS = 'OPTIONS';
    private $_getVars  = array();
    private $_postVars = array();
    private $_headers  = array();
    private $_cookies  = array();

    private $_executionMode;

    private $_uri;
    private $_host;
    private $_protocol        = 'HTTP';
    private $_port            = 80;
    private $_method          = self::METHOD_GET;
    private $_userAgent;
    private $_basicAuthenticationData;
    private $_attachments     = array();
    private $_timeout         = 30;
    private $_customBodyParts = array();
    private $_defaultHeaders  = array(
        'Accept'          => '*/*',
        'Content-type'    => 'application/x-www-form-urlencoded',
        'Accept-Language' => '*',
        'Accept-Encoding' => '*',
        'Accept-Charset'  => '*',
    );

    /**
     * @var ArrayStorage
     */
    private $_vars;
    /**
     * @var Request
     */
    static private $_incomeRequestInstance;

    public function __construct() {
        $this->_vars = new ArrayStorage();
    }

    public static function createInstance() {
        return new Request();
    }

    public static function getIncomeRequest() {
        if (!self::$_incomeRequestInstance) {
            self::$_incomeRequestInstance = new Request();
            self::$_incomeRequestInstance->processEnvironment();
        }
        return self::$_incomeRequestInstance;
    }

    private function processEnvironment() {
        $this->_method   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : Request::MODE_CONSOLE;
        $this->_host     = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $this->_protocol = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '');
        if (!empty($_SERVER['HTTP_X_SCHEME']) && ($_SERVER['HTTP_X_SCHEME'] == 'https')) {
            $this->_protocol = 'https';
        }
        $this->detectExecutionMode();

        $this->processHeaders();
        if (!$this->isConsoleRequest()) {
            $this->processRequestOptionsMethod();
        }

        $this->setVars($_GET);
        if ($this->isJsonRequest()) {
            $data = convertObjectToArray(json_decode(file_get_contents("php://input")));
            $this->setVars($data);
        } else {
            $this->setVars($_POST);
        }

        $this->detectUri();
    }

    private function detectExecutionMode() {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->_executionMode = Request::MODE_CONSOLE;
        }
    }

    private function detectUri() {
        if ($this->_executionMode != Request::MODE_CONSOLE) {
            $this->_uri = str_replace('%20', ' ', $_SERVER['REQUEST_URI'] . '');
            if (!empty($_SERVER['QUERY_STRING'])) {
                $this->_uri = substr($this->_uri, 0, -strlen($_SERVER['QUERY_STRING']) - 1);
            }
            if (strlen($this->_uri) > 1 && $this->_uri[0] == '/') {
                $this->_uri = substr($this->_uri, 1);
            }
        } else {
            $this->_uri = '/' . (!empty($_SERVER['argv'][1]) ? str_replace(':', '/', $_SERVER['argv'][1]) : '');
        }

    }

    private function processHeaders() {
        $data           = getallheaders();
        $this->_headers = array();
        foreach ($data as $key => $value) {
            $this->_headers[strtolower($key)] = $value;
        }

    }

    private function processRequestOptionsMethod() {
        $headers = array(
            'Access-Control-Allow-Headers:accept,authorization,content-type,session-token',
            'Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Origin:*'
        );
        foreach ($headers as $header) {
            header($header);
        }
        if ($this->_method == Request::METHOD_OPTIONS) {
            die();
        }
    }

    public function isConsoleRequest() {
        return $this->_executionMode == Request::MODE_CONSOLE;
    }

    public function isXHR() {
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
            || (isset($_REQUEST['XHR_REQUEST']))
            || !empty($_REQUEST['IFRAME_FORM_SENT'])
        ) {
            return true;
        }
        return false;
    }

    public function isJsonRequest() {
        return strpos($this->getContentType(), 'application/json') === 0;
    }

    public function getContentType() {
        return !empty($this->_headers['content-type']) ? $this->_headers['content-type'] : '';
    }

    public function getExecutionMode() {
        return $this->_executionMode;
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function getHeader($name) {
        return !empty($this->_headers[$name]) ? $this->_headers[$name] : '';
    }

    public function getIpAddress() {
        $ip = '127.0.0.1';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function getMethod() {
        return $this->_method;
    }

    public function getUri() {
        return $this->_uri;
    }

    public function getUserAgent() {
        return $this->_userAgent;
    }

    public function getPort() {
        return $this->_port;
    }

    public function getProtocol() {
        return $this->_protocol;
    }

    public function getHost() {
        return $this->_host;
    }

    /*
     * ################### SETTERS
     */


    public function setBasicAuthenticationData($user, $password) {
        $this->_basicAuthenticationData = $user . ':' . $password;
        return $this;
    }

    public function setCookie($name, $value) {
        $this->_cookies[$name] = $value;
    }

    public function setHeader($name, $value) {
        $this->_headers[$name] = $value;
        return $this;
    }

    public function setHost($host) {
        $this->_host = $host;
        return $this;
    }

    public function setMethod($method) {
        $this->_method = $method;
        return $this;
    }

    public function setPort($port) {
        $this->_port = $port;
        return $this;
    }

    public function setProtocol($protocol) {
        $this->_protocol = strtoupper($protocol);
        return $this;
    }

    public function setUserAgent($userAgent) {
        $this->_userAgent = $userAgent;
    }


    public function setVar($name, $value) {
        $this->_vars->setDeepValue($name, $value);
        return $this;
    }

    public function setVars($array) {
        foreach ($array as $key => $value) {
            $this->setVar($key, $value);
        }
        return $this;
    }

    public function setUri($uri) {
        if (strpos($uri, '://') !== false) {
            $info = null;
            preg_match('#(.+)://(.+)/(.*)#', $uri, $info);
            $this->setProtocol($info[1]);
            if ($this->_protocol == 'HTTPS') {
                $this->setPort(443);
            }
            $this->setHost($info[2]);
            $this->_uri = $info[3];
        } else {
            $this->_uri = $uri;
        }
        if ($this->_uri[0] !== '/') {
            $this->_uri = '/' . $this->_uri;
        }
        return $this;
    }

    public function setGETVar($name, $value) {
        $this->_getVars[$name] = $value;
        return $this;
    }

    public function setGETVars($array) {
        foreach ($array as $key => $value) {
            $this->setGETVar($key, $value);
        }
        return $this;
    }

    public function setPOSTVar($name, $value) {
        $this->_getVars[$name] = $value;
        return $this;
    }

    public function setPOSTVars($array) {
        foreach ($array as $key => $value) {
            $this->setPOSTVar($key, $value);
        }
        return $this;
    }

    /** SENDING REQUEST */
    public function addCustomBodyPart($bodyPart) {
        $this->_customBodyParts[] = $bodyPart;
    }

    public function addAttachmentReference($name, $fileLocation) {
        $this->_attachments[] = array(
            'name' => $name,
            'type' => 'reference',
            'path' => $fileLocation
        );
        return $this;
    }

    private function fillDefaultHeaders() {
        foreach ($this->_defaultHeaders as $name => $value) {
            if (!array_key_exists($name, $this->_headers)) {
                $this->setHeader($name, $value);
            }
        }
    }

    public function send() {

        $this->fillDefaultHeaders();
        $CRLF = "\r\n";

        $getData        = '?';
        $postData       = '';
        $customBodyData = '';


        foreach ($this->_getVars as $name => $value) {
            $getData .= rawurlencode($name) . '=' . rawurlencode($value) . '&';
        }
        $getData = substr($getData, 0, -1);

        $request = $this->_method . ' ' . $this->_protocol . '://' . $this->_host . $this->_uri . $getData . ' HTTP/1.1' . $CRLF;
        $request .= 'Host: ' . $this->_host . $CRLF;
        $request .= 'User-agent: ' . $this->_userAgent . $CRLF;

        if ($this->_basicAuthenticationData) {
            $request .= 'Authorization: Basic ' . base64_encode($this->_basicAuthenticationData) . $CRLF;
        }

        foreach ($this->_headers as $name => $value) {
            $request .= $name . ': ' . $value . $CRLF;
        }

        $request .= 'Connection: close' . $CRLF;

        if ((($this->_method == Request::METHOD_POST) || ($this->_method == Request::METHOD_PUT)) && (!empty($this->_post) || !empty($this->_attachments) || !empty($this->_customBodyParts))) {
            if (empty($this->_attachments)) {
                $request .= 'Content-Type: ' . $this->getContentType() . $CRLF;
                $postData = http_build_query($this->_post);
            } else {
                $boundary = md5(uniqid(time()));
                $request .= 'Content-Type: multipart/form-data; boundary=' . $boundary . $CRLF;
                foreach ($this->_post as $name => $value) {
                    $postData .= '--' . $boundary . $CRLF;
                    $postData .= 'Content-Disposition: form-data; name="' . $name . '"' . $CRLF . $CRLF . $value . $CRLF;
                }
                foreach ($this->_attachments as $attachment) {
                    if ($attachment['type'] == 'reference' && is_file($attachment['path'])) {
                        $file_name = basename($attachment['path']);

                        $postData .= '--' . $boundary . $CRLF;
                        $postData .= 'Content-Disposition: form-data; name="' . $attachment['path'] . '"; filename="' . $file_name . '"' . $CRLF;
                        $postData .= 'Content-Type: application/octet-stream' . $CRLF . $CRLF;
                        $postData .= file_get_contents($attachment['path']) . $CRLF;
                    }
                }
                $postData .= '--' . $boundary . '--' . $CRLF;
            }
            foreach ($this->_customBodyParts as $part) {
                $customBodyData .= $part;
            }
            $request .= 'Content-Length: ' . (strlen($postData) + strlen($customBodyData)) . $CRLF . $CRLF;
            if ($postData) $request .= $postData . $CRLF;
            if ($customBodyData) $request .= $customBodyData . $CRLF;
        }
        $request .= $CRLF;
        $errorNumber  = null;
        $errorMessage = '';
        $handler      = fsockopen((($this->_protocol == 'https') ? 'ssl://' : '') . $this->_host, $this->_port, $errorNumber, $errorMessage, $this->_timeout);

        fputs($handler, $request);

        $contentData    = '';
        $processHeaders = true;
        $headerData     = '';
        while ($line = fgets($handler)) {
            if (($line == "\n") || ($line == "\r\n")) $processHeaders = false;
            if ($processHeaders) {
                $headerData .= $line;
            } else {
                $contentData .= $line;
            }
//            $processHeaders ? $responseHeaders[] = $line : $responseData .= $line;
        }
        fclose($handler);

        if (strpos(strtolower($headerData), "transfer-encoding: chunked") !== false) {
            $contentData = self::unchunk($contentData);
        }
        if (strpos(strtolower($headerData), "content-encoding: gzip") !== false) {
            $contentData = gzinflate(substr($contentData, 10, -8));//gzdecode($results);
        } else if (strpos(strtolower($headerData), "content-encoding: deflate") !== false) {
            $contentData = gzinflate($contentData);
        }
        $response = new Response($contentData, 200, $headerData);
        return $response;
    }

    static private function unchunk($data) {
        return preg_replace('/([0-9A-F]+)\r\n(.*)/sie',
            '($cnt=@base_convert("\1", 16, 10))
                               ?substr(($str=@strtr(\'\2\', array(\'\"\'=>\'"\', \'\\\\0\'=>"\x00"))), 0, $cnt).slRequest::unchunk(substr($str, $cnt+2))
                               :""
                              ',
            $data
        );
    }

}


/**
 * for old version of php
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}