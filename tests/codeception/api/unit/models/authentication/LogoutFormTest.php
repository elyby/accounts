<?php
namespace tests\codeception\api\models\authentication;

use api\components\User\Component;
use api\models\AccountIdentity;
use api\models\authentication\LogoutForm;
use Codeception\Specify;
use common\models\AccountSession;
use tests\codeception\api\unit\TestCase;
use Yii;

class LogoutFormTest extends TestCase {
    use Specify;

    public function testValidateLogout() {
        $this->specify('No actions if active session is not exists', function () {
            $userComp = $this
                ->getMockBuilder(Component::class)
                ->setConstructorArgs([$this->getComponentArgs()])
                ->setMethods(['getActiveSession'])
                ->getMock();
            $userComp
                ->expects($this->any())
                ->method('getActiveSession')
                ->will($this->returnValue(null));

            Yii::$app->set('user', $userComp);

            $model = new LogoutForm();
            expect($model->logout())->true();
        });

        $this->specify('if active session is presented, then delete should be called', function () {
            $session = $this
                ->getMockBuilder(AccountSession::class)
                ->setMethods(['delete'])
                ->getMock();
            $session
                ->expects($this->once())
                ->method('delete')
                ->willReturn(true);

            $userComp = $this
                ->getMockBuilder(Component::class)
                ->setConstructorArgs([$this->getComponentArgs()])
                ->setMethods(['getActiveSession'])
                ->getMock();
            $userComp
                ->expects($this->any())
                ->method('getActiveSession')
                ->will($this->returnValue($session));

            Yii::$app->set('user', $userComp);

            $model = new LogoutForm();
            $model->logout();
        });
    }

    private function getComponentArgs() {
        return [
            'identityClass' => AccountIdentity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ];
    }

}
