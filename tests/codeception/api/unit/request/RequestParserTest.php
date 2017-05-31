<?php
namespace tests\codeception\api\unit\request;

use api\request\RequestParser;
use tests\codeception\api\unit\TestCase;

class RequestParserTest extends TestCase {

    public function testParse() {
        $parser = new RequestParser();
        $_POST = ['from' => 'post'];
        $this->assertEquals(['from' => 'post'], $parser->parse('from=post', ''));
        $this->assertEquals(['from' => 'post'], $parser->parse('', ''));
        $_POST = [];
        $this->assertEquals(['from' => 'json'], $parser->parse('{"from":"json"}', ''));
        $this->assertEquals(['from' => 'body'], $parser->parse('from=body', ''));
        $this->assertEquals(['onlykey' => ''], $parser->parse('onlykey', ''));
    }

}
