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

    const MODE_WEB     = 'web';
    const MODE_CONSOLE = 'console';

    /**
     * @var ArrayStorage
     */
    private $_post;
    /**
     * @var ArrayStorage
     */
    private $_get;
    /**
     * @var ArrayStorage
     */
    private $_headers;
    /**
     * @var ArrayStorage
     */
    private $_cookies;
    /**
     * @var ArrayStorage
     */
    private $_server;

    private $_executionMode;

    private $_uri;
    private $_host;
    private $_scheme;
    private $_port;
    private $_method;
    private $_userAgent;
    /**
     * @var Request
     */
    static private $_mainInstance;

    public static function getMainInstance() {
        if (!self::$_mainInstance) {
            self::$_mainInstance = new Request();
//            self::$_mainInstance->processEnvironment();
        }
        return self::$_mainInstance;
    }

}