<?php
namespace tests\codeception\common\_support;

use Codeception\Module;
use Codeception\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\AccountSessionFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\common\fixtures\OauthClientFixture;
use tests\codeception\common\fixtures\OauthSessionFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;
use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

/**
 * This helper is used to populate the database with needed fixtures before any tests are run.
 * All fixtures will be loaded before the suite is started and unloaded after it completes.
 */
class FixtureHelper extends Module {

    /**
     * Redeclare visibility because codeception includes all public methods that do not start with "_"
     * and are not excluded by module settings, in actor class.
     */
    use FixtureTrait {
        loadFixtures as protected;
        fixtures as protected;
        globalFixtures as protected;
        unloadFixtures as protected;
        getFixtures as protected;
        getFixture as protected;
    }

    public function _before(TestCase $test) {
        $this->loadFixtures();
    }

    public function _after(TestCase $test) {
        $this->unloadFixtures();
    }

    public function globalFixtures() {
        return [
            InitDbFixture::className(),
        ];
    }

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'accountSessions' => AccountSessionFixture::class,
            'emailActivations' => EmailActivationFixture::class,
            'usernamesHistory' => UsernameHistoryFixture::class,
            'oauthClients' => [
                'class' => OauthClientFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/oauth-clients.php',
            ],
            'oauthSessions' => [
                'class' => OauthSessionFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/oauth-sessions.php',
            ],
        ];
    }

}
