<?php
use tests\codeception\api\AcceptanceTester;
use tests\codeception\api\_pages\AboutPage;

/* @var $scenario Codeception\Scenario */

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that about works');
AboutPage::openBy($I);
$I->see('About', 'h1');
