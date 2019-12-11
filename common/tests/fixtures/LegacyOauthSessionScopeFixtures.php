<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\tests\_support\Redis\Fixture;

class LegacyOauthSessionScopeFixtures extends Fixture {

    public $dataFile = '@root/common/tests/fixtures/data/legacy-oauth-sessions-scopes.php';

    public $keysPrefix = 'oauth:sessions:';

    public $keysPostfix = ':scopes';

}
