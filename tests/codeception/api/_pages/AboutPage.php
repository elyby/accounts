<?php

namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * Represents about page
 * @property \codeception_api\AcceptanceTester|\codeception_api\FunctionalTester $actor
 */
class AboutPage extends BasePage
{
    public $route = 'site/about';
}
