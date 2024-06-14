<?php
declare(strict_types=1);

namespace common\tests\_support;

use Codeception\Module;
use Codeception\TestInterface;
use common\tests\fixtures;
use yii\test\FixtureTrait;
use yii\test\InitDbFixture;

/**
 * This helper is used to populate the database with needed fixtures before any tests are run.
 * All fixtures will be loaded before the suite is started and unloaded after it completes.
 *
 * TODO: try to remove
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

    public function _before(TestInterface $test) {
        $this->loadFixtures();
    }

    public function _after(TestInterface $test) {
        $this->unloadFixtures();
    }

    public function globalFixtures() {
        return [
            InitDbFixture::class,
        ];
    }

    public function fixtures() {
        return [
            'accounts' => fixtures\AccountFixture::class,
            'accountSessions' => fixtures\AccountSessionFixture::class,
            'emailActivations' => fixtures\EmailActivationFixture::class,
            'usernamesHistory' => fixtures\UsernameHistoryFixture::class,
            'oauthClients' => fixtures\OauthClientFixture::class,
            'oauthSessions' => fixtures\OauthSessionFixture::class,
            'legacyOauthSessionsScopes' => fixtures\LegacyOauthSessionScopeFixtures::class,
            'legacyOauthAccessTokens' => fixtures\LegacyOauthAccessTokenFixture::class,
            'legacyOauthAccessTokensScopes' => fixtures\LegacyOauthAccessTokenScopeFixture::class,
            'legacyOauthRefreshTokens' => fixtures\LegacyOauthRefreshTokenFixture::class,
        ];
    }

}
