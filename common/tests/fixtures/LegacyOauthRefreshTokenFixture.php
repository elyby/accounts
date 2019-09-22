<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\tests\_support\Redis\Fixture;

class LegacyOauthRefreshTokenFixture extends Fixture {

    public $dataFile = '@root/common/tests/fixtures/data/legacy-oauth-refresh-tokens.php';

    public $keysPrefix = 'oauth:refresh:tokens:';

}
