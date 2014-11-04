<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 10/20/14 7:07 PM
 */

namespace Solve\Http;

use Solve\Storage\ArrayStorage;

/**
 * Class Response
 * @package Solve\Http
 *
 * Class Response is used to generate correct response from application
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class Response {
    const PROTOCOL_HTTP = 'HTTP';

    /**
     * @var HeaderStorage
     */
    private $_headers;
    private $_cookies     = array();
    private $_content     = null;
    private $_charset     = 'UTF-8';
    private $_protocol    = Response::PROTOCOL_HTTP;
    private $_statusCode  = 200;
    private $_statusText  = '';


    public function __construct($content = null, $statusCode = 200, $headers = array()) {
        $this->_content    = $content;
        $this->_statusCode = $statusCode;
        $this->_statusText = HttpStatus::$statusTexts[$statusCode];
        $this->_headers    = new HeaderStorage();
        if (!empty($headers)) {
            $this->parseHeaders($headers);
        }
    }

    public function send() {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            ob_end_flush();
            flush();
        }

        return $this;
    }

    public function sendContent() {
        echo $this->_content;
        return $this;
    }

    public function sendHeaders() {
        if (headers_sent()) {
            return false;
        }

        header(sprintf('HTTP/%s %s %s', '1.1', $this->_statusCode, $this->_statusText), true, $this->_statusCode);

        foreach ($this->_headers as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false, $this->_statusCode);
            }
        }

        foreach ($this->_cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expiresTime'], $cookie['path'], $cookie['domain'], $cookie['isSecure'], $cookie['isHttpOnly']);
        }

        return $this;
    }

    public function setNotModified() {
        $this->setStatusCode(304);
        $this->setContent(null);

        // remove headers that MUST NOT be included with 304 Not Modified responses
        foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
            $this->_headers->remove($header);
        }

        return $this;
    }

    public function setDate(\DateTime $date) {
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->_headers->set('Date', $date->format('D, d M Y H:i:s') . ' GMT');

        return $this;
    }

    public function setLastModified(\DateTime $date = null) {
        if (null === $date) {
            $this->_headers->remove('Last-Modified');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->_headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    public function setExpires(\DateTime $date = null) {
        if (null === $date) {
            $this->_headers->remove('Expires');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->_headers->set('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    public function setCharset($charset) {
        $this->_charset = $charset;

        return $this;
    }

    public function setCookie($name, $value, $expiresTime = null, $path = null, $domain = null, $isSecure = null, $isHttpOnly = null) {
        $this->_cookies[] = array(
            'name'        => $name,
            'value'       => $value,
            'expiresTime' => $expiresTime,
            'path'        => $path,
            'isSecure'    => $isSecure,
            'isHttpOnly'  => $isHttpOnly,
            'domain'      => $domain,
        );
        return $this;
    }

    /**
     * @param array|mixed $headers
     */
    public function parseHeaders($headers) {
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }
        foreach ($headers as $header) {
            if (strpos($header, ':') === false) {
                $info = explode(' ', $header);
                if (count($info) > 2) {
                    $protocol = explode('/', $info[0]);
                    $this->setProtocol($protocol[0]);
                    $this->setStatusCode($info[1]);
                }
            } else {
                $this->_headers->addFromString($header);
            }
        }
        if ($this->_headers->has('Set-Cookie')) {
            foreach ($this->_headers->get('Set-Cookie') as $cookieInfo) {
                $posEquals                   = strpos($cookieInfo, '=');
                $cookieName                  = substr($cookieInfo, 0, $posEquals);
                $cookieValue                 = substr($cookieInfo, $posEquals + 1);
                $this->_cookies[$cookieName] = $cookieValue;
            }
        }
    }

    public function setHeader($name, $value) {
        $this->_headers->set($name, $value);
        return $this;
    }

    public function getHeader($name) {
        return $this->_headers->get($name);
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function getContent() {
        return $this->_content;
    }

    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    public function getProtocol() {
        return $this->_protocol;
    }

    public function setProtocol($protocol) {
        $this->_protocol = strtoupper($protocol);
        return $this;
    }

    public function getStatusCode() {
        return $this->_statusCode;
    }

    public function getStatusText() {
        return HttpStatus::$statusTexts[$this->_statusCode];
    }

    public function setStatusCode($statusCode) {
        $this->_statusCode = (int)$statusCode;
        $this->_statusText = HttpStatus::$statusTexts[$statusCode];
        return $this;
    }

    public function isSuccessful() {
        return $this->_statusCode >= 200 && $this->_statusCode < 300;
    }

    public function isError() {
        return $this->_statusCode >= 400 && $this->_statusCode < 600;
    }

    public function isRedirection() {
        return $this->_statusCode >= 300 && $this->_statusCode < 400;
    }

    public function isClientError() {
        return $this->_statusCode >= 400 && $this->_statusCode < 500;
    }

    public function isServerError() {
        return $this->_statusCode >= 500 && $this->_statusCode < 600;
    }

    public function isOk() {
        return 200 === $this->_statusCode;
    }

    public function isForbidden() {
        return 403 === $this->_statusCode;
    }

    public function isNotFound() {
        return 404 === $this->_statusCode;
    }


}