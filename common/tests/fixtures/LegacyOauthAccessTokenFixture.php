<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\tests\_support\Redis\Fixture;

class LegacyOauthAccessTokenFixture extends Fixture {

    public $dataFile = '@root/common/tests/fixtures/data/legacy-oauth-access-tokens.php';

    public $keysPrefix = 'oauth:access:tokens:';

}
