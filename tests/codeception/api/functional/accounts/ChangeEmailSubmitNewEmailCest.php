<?php
namespace tests\codeception\api\functional\accounts;

use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\FunctionalTester;
use tests\codeception\common\helpers\Mock;
use yii\validators\EmailValidator;

class ChangeEmailSubmitNewEmailCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testSubmitNewEmail(FunctionalTester $I) {
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturnTrue();

        $I->wantTo('submit new email');
        $id = $I->amAuthenticated('ILLIMUNATI');

        $this->route->changeEmailSubmitNewEmail($id, 'H27HBDCHHAG2HGHGHS', 'my-new-email@ely.by');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
