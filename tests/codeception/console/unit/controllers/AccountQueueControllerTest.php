<?php
namespace codeception\console\unit\controllers;

use Codeception\Specify;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use console\controllers\AccountQueueController;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\MojangUsernameFixture;
use tests\codeception\console\unit\DbTestCase;
use Yii;

/**
 * @property array $accounts
 * @property array $mojangUsernames
 */
class AccountQueueControllerTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
            'mojangUsernames' => [
                'class' => MojangUsernameFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/mojang-usernames.php',
            ],
        ];
    }

    public function testRouteUsernameChanged() {
        // TODO: пропустить тест, если у нас нету интернета
        $controller = new AccountQueueController('account-queue', Yii::$app);
        $this->specify('Update last_pulled_at time if username exists', function() use ($controller) {
            $accountInfo = $this->accounts['admin'];
            $body = new UsernameChanged([
                'accountId' => $accountInfo['id'],
                'oldUsername' => $accountInfo['username'],
                'newUsername' => 'Notch',
            ]);
            $controller->routeUsernameChanged($body);
            /** @var MojangUsername|null $mojangUsername */
            $mojangUsername = MojangUsername::findOne('Notch');
            expect($mojangUsername)->isInstanceOf(MojangUsername::class);
            expect($mojangUsername->last_pulled_at)->greaterThan($this->mojangUsernames['Notch']['last_pulled_at']);
            expect($mojangUsername->last_pulled_at)->lessOrEquals(time());
        });

        $this->specify('Add new MojangUsername if don\'t exists', function() use ($controller) {
            $accountInfo = $this->accounts['admin'];
            $body = new UsernameChanged([
                'accountId' => $accountInfo['id'],
                'oldUsername' => $accountInfo['username'],
                'newUsername' => 'Chest',
            ]);
            $controller->routeUsernameChanged($body);
            /** @var MojangUsername|null $mojangUsername */
            $mojangUsername = MojangUsername::findOne('Chest');
            expect($mojangUsername)->isInstanceOf(MojangUsername::class);
        });

        $this->specify('Remove MojangUsername, if now it\'s does\'t exists', function() use ($controller) {
            $accountInfo = $this->accounts['admin'];
            $username = $this->mojangUsernames['not-exists']['username'];
            $body = new UsernameChanged([
                'accountId' => $accountInfo['id'],
                'oldUsername' => $accountInfo['username'],
                'newUsername' => $username,
            ]);
            $controller->routeUsernameChanged($body);
            /** @var MojangUsername|null $mojangUsername */
            $mojangUsername = MojangUsername::findOne($username);
            expect($mojangUsername)->null();
        });

        $this->specify('Update uuid if username for now owned by other player', function() use ($controller) {
            $accountInfo = $this->accounts['admin'];
            $mojangInfo = $this->mojangUsernames['uuid-changed'];
            $username = $mojangInfo['username'];
            $body = new UsernameChanged([
                'accountId' => $accountInfo['id'],
                'oldUsername' => $accountInfo['username'],
                'newUsername' => $username,
            ]);
            $controller->routeUsernameChanged($body);
            /** @var MojangUsername|null $mojangUsername */
            $mojangUsername = MojangUsername::findOne($username);
            expect($mojangUsername)->isInstanceOf(MojangUsername::class);
            expect($mojangUsername->uuid)->notEquals($mojangInfo['uuid']);
        });
    }

}
