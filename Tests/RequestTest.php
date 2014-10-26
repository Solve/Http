<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 21.10.14 07:55
 */

namespace Solve\Http\Tests;

use Solve\Http\Request;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Request.php';
require_once __DIR__ . '/../HeaderStorage.php';
require_once __DIR__ . '/../HttpStatus.php';
require_once __DIR__ . '/../Response.php';

class RequestTest extends \PHPUnit_Framework_TestCase {

    public function testBasic() {
        $request = Request::getIncomeRequest();
        $this->assertEquals(Request::MODE_CONSOLE, $request->getExecutionMode(), 'Console request detected');

        $response = Request::createInstance()->setHost('google.com')->send();

        $this->assertTrue($response->isRedirection(), 'google.com returns redirection');
        if ($response->isRedirection()) {
            $response = Request::createInstance()->setUri($response->getHeader('Location'))->send();
        }

        $this->assertTrue($response->isOk(), 'got it after redirect');
    }

    public function testRequestServer() {
        $_SERVER['REQUEST_METHOD'] = Request::METHOD_GET;
        $_SERVER['REQUEST_URI']    = '/products/2/';
        $_SERVER['QUERY_STRING']   = 'id=12';
        $_SERVER['HTTP_HOST']      = 'test.com';
        $_SERVER['DOCUMENT_ROOT']      = '/';
        $request                   = new Request();
        $request->processEnvironment();
        $this->assertEquals(12, $request->getVar('id'), 'parsing query string');
    }

}
 