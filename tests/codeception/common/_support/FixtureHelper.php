<?php

namespace tests\codeception\common\_support;

use Codeception\Module;
use tests\codeception\common\fixtures\AccountFixture;
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

    /**
     * Method called before any suite tests run. Loads User fixture login user
     * to use in functional tests.
     *
     * @param array $settings
     */
    public function _beforeSuite($settings = []) {
        $this->loadFixtures();
    }

    /**
     * Method is called after all suite tests run
     */
    public function _afterSuite() {
        $this->unloadFixtures();
    }

    /**
     * @inheritdoc
     */
    public function globalFixtures() {
        return [
            InitDbFixture::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }
}
