<?php
declare(strict_types=1);

namespace api\tests\unit\rbac\rules;

use api\components\User\IdentityInterface;
use api\rbac\Permissions as P;
use api\rbac\rules\OauthClientOwner;
use common\models\Account;
use common\tests\fixtures\OauthClientFixture;
use common\tests\unit\TestCase;
use InvalidArgumentException;
use Yii;
use yii\rbac\Item;
use const common\LATEST_RULES_VERSION;

class OauthClientOwnerTest extends TestCase {

    public function _fixtures(): array {
        return [
            'oauthClients' => OauthClientFixture::class,
        ];
    }

    public function testExecute(): void {
        $rule = new OauthClientOwner();
        $item = new Item();

        // Client not exists (we expect true to let controller throw corresponding 404 exception)
        $this->assertTrue($rule->execute('some token', $item, ['clientId' => 'not exists client id']));

        // Client exists, but identity is null
        $this->assertFalse($rule->execute('some token', $item, ['clientId' => 'ely']));

        // Client exists, identity presented, but have no account
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getAccount')->willReturn(null);
        Yii::$app->user->setIdentity($identity);

        $this->assertFalse($rule->execute('some token', $item, ['clientId' => 'ely']));

        // Identity has an account
        $account = new Account();
        $account->id = 1;
        $account->status = Account::STATUS_ACTIVE;
        $account->rules_agreement_version = LATEST_RULES_VERSION;

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getAccount')->willReturn($account);
        Yii::$app->user->setIdentity($identity);

        $this->assertTrue($rule->execute('token', $item, ['clientId' => 'admin-oauth-client']));
        $this->assertTrue($rule->execute('token', $item, ['clientId' => 'not-exists-client']));
        $account->id = 2;
        $this->assertFalse($rule->execute('token', $item, ['clientId' => 'admin-oauth-client']));
        $item->name = P::VIEW_OWN_OAUTH_CLIENTS;
        $this->assertTrue($rule->execute('token', $item, ['accountId' => 2]));
        $this->assertFalse($rule->execute('token', $item, ['accountId' => 1]));
    }

    public function testExecuteWithoutClientId(): void {
        $this->expectException(InvalidArgumentException::class);

        $rule = new OauthClientOwner();
        $this->assertFalse($rule->execute('token', new Item(), []));
    }

}
