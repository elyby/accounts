<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\components\User\Component;
use api\models\authentication\LogoutForm;
use api\tests\unit\TestCase;
use common\models\AccountSession;
use Yii;

class LogoutFormTest extends TestCase {

    public function testNoActionWhenThereIsNoActiveSession() {
        $userComp = $this->createPartialMock(Component::class, ['getActiveSession']);
        $userComp->method('getActiveSession')->willReturn(null);

        Yii::$app->set('user', $userComp);

        $model = new LogoutForm();
        $this->assertTrue($model->logout());
    }

    public function testActiveSessionShouldBeDeleted() {
        $session = $this->createPartialMock(AccountSession::class, ['delete']);
        $session->expects($this->once())->method('delete')->willReturn(true);

        $userComp = $this->createPartialMock(Component::class, ['getActiveSession']);
        $userComp->method('getActiveSession')->willReturn($session);

        Yii::$app->set('user', $userComp);

        $model = new LogoutForm();
        $model->logout();
    }

}
