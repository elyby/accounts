<?php
namespace tests\codeception\common\unit\models;

use tests\codeception\common\unit\TestCase;

class OauthScopeTest extends TestCase {

    public function testTest() {
        $scopes = \common\models\OauthScope::getScopes();
    }

}
