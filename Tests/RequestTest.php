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
        var_dump($response);die();
        var_dump($response->getHeaders()->getAll());die();
        if ($response->isRedirection()) {
        }
        var_dump($response->getContent());die();

        die('1');
    }

}
 