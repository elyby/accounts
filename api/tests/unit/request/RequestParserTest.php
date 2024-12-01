<?php
namespace api\tests\unit\request;

use api\request\RequestParser;
use api\tests\unit\TestCase;

class RequestParserTest extends TestCase {

    public function testParse(): void {
        $parser = new RequestParser();
        $_POST = ['from' => 'post'];
        $this->assertSame(['from' => 'post'], $parser->parse('from=post', ''));
        $this->assertSame(['from' => 'post'], $parser->parse('', ''));
        $_POST = [];
        $this->assertSame(['from' => 'json'], $parser->parse('{"from":"json"}', ''));
        $this->assertSame(['from' => 'body'], $parser->parse('from=body', ''));
        $this->assertSame(['onlykey' => ''], $parser->parse('onlykey', ''));
    }

}
