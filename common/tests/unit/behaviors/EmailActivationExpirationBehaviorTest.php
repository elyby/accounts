<?php
namespace common\tests\unit\behaviors;

use Codeception\Specify;
use common\behaviors\EmailActivationExpirationBehavior;
use common\tests\_support\ProtectedCaller;
use common\tests\unit\TestCase;
use yii\base\Model;

class EmailActivationExpirationBehaviorTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function testCalculateTime() {
        $behavior = $this->createBehavior();
        $time = time();
        $behavior->owner->created_at = $time;
        $this->assertSame($time + 10, $this->callProtected($behavior, 'calculateTime', 10));
    }

    public function testCompareTime() {
        $this->specify('expect false, if passed value is less then 0', function() {
            $behavior = $this->createBehavior();
            $this->assertFalse($this->callProtected($behavior, 'compareTime', -1));
        });

        $this->specify('expect true, if passed value is equals 0', function() {
            $behavior = $this->createBehavior();
            $this->assertTrue($this->callProtected($behavior, 'compareTime', 0));
        });

        $this->specify('expect true, if passed value is more than 0 and current time is greater then calculated', function() {
            $behavior = $this->createBehavior();
            $behavior->owner->created_at = time() - 10;
            $this->assertTrue($this->callProtected($behavior, 'compareTime', 5));
        });

        $this->specify('expect false, if passed value is more than 0 and current time is less then calculated', function() {
            $behavior = $this->createBehavior();
            $behavior->owner->created_at = time() - 2;
            $this->assertFalse($this->callProtected($behavior, 'compareTime', 7));
        });
    }

    public function testCanRepeat() {
        $this->specify('we can repeat, if created_at + repeatTimeout is greater, then current time', function() {
            $behavior = $this->createBehavior();
            $behavior->repeatTimeout = 30;
            $behavior->owner->created_at = time() - 60;
            $this->assertTrue($behavior->canRepeat());
        });

        $this->specify('we cannot repeat, if created_at + repeatTimeout is less, then current time', function() {
            $behavior = $this->createBehavior();
            $behavior->repeatTimeout = 60;
            $behavior->owner->created_at = time() - 30;
            $this->assertFalse($behavior->canRepeat());
        });
    }

    public function testIsExpired() {
        $this->specify('key is not expired, if created_at + expirationTimeout is greater, then current time', function() {
            $behavior = $this->createBehavior();
            $behavior->expirationTimeout = 30;
            $behavior->owner->created_at = time() - 60;
            $this->assertTrue($behavior->isExpired());
        });

        $this->specify('key is not expired, if created_at + expirationTimeout is less, then current time', function() {
            $behavior = $this->createBehavior();
            $behavior->expirationTimeout = 60;
            $behavior->owner->created_at = time() - 30;
            $this->assertFalse($behavior->isExpired());
        });
    }

    public function testCanRepeatIn() {
        $this->specify('get expected timestamp for repeat time moment', function() {
            $behavior = $this->createBehavior();
            $behavior->repeatTimeout = 30;
            $behavior->owner->created_at = time() - 60;
            $this->assertSame($behavior->owner->created_at + $behavior->repeatTimeout, $behavior->canRepeatIn());
        });
    }

    public function testExpireIn() {
        $this->specify('get expected timestamp for key expire moment', function() {
            $behavior = $this->createBehavior();
            $behavior->expirationTimeout = 30;
            $behavior->owner->created_at = time() - 60;
            $this->assertSame($behavior->owner->created_at + $behavior->expirationTimeout, $behavior->expireIn());
        });
    }

    /**
     * @return EmailActivationExpirationBehavior
     */
    private function createBehavior() {
        $behavior = new EmailActivationExpirationBehavior();
        /** @var Model $model */
        $model = new class extends Model {
            public $created_at;
        };
        $model->attachBehavior('email-activation-behavior', $behavior);

        return $behavior;
    }

}
