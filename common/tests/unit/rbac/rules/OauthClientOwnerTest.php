<?php
namespace common\tests\unit\rbac\rules;

use api\components\User\Component;
use api\components\User\IdentityInterface;
use common\models\Account;
use common\rbac\Permissions as P;
use common\rbac\rules\OauthClientOwner;
use common\tests\fixtures\OauthClientFixture;
use common\tests\unit\TestCase;
use Yii;
use yii\rbac\Item;
use const common\LATEST_RULES_VERSION;

class OauthClientOwnerTest extends TestCase {

    public function _fixtures(): array {
        return [
            'oauthClients' => OauthClientFixture::class,
        ];
    }

    public function testExecute() {
        $rule = new OauthClientOwner();
        $item = new Item();

        $account = new Account();
        $account->id = 1;
        $account->status = Account::STATUS_ACTIVE;
        $account->rules_agreement_version = LATEST_RULES_VERSION;

        /** @var IdentityInterface|\Mockery\MockInterface $identity */
        $identity = mock(IdentityInterface::class);
        $identity->shouldReceive('getAccount')->andReturn($account);

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[findIdentityByAccessToken]', [[
            'secret' => 'secret',
            'publicKeyPath' => 'data/certs/public.crt',
            'privateKeyPath' => 'data/certs/private.key',
        ]]);
        $component->shouldDeferMissing();
        $component->shouldReceive('findIdentityByAccessToken')->withArgs(['token'])->andReturn($identity);

        Yii::$app->set('user', $component);

        $this->assertFalse($rule->execute('token', $item, []));
        $this->assertTrue($rule->execute('token', $item, ['clientId' => 'admin-oauth-client']));
        $this->assertTrue($rule->execute('token', $item, ['clientId' => 'not-exists-client']));
        $account->id = 2;
        $this->assertFalse($rule->execute('token', $item, ['clientId' => 'admin-oauth-client']));
        $item->name = P::VIEW_OWN_OAUTH_CLIENTS;
        $this->assertTrue($rule->execute('token', $item, ['accountId' => 2]));
        $this->assertFalse($rule->execute('token', $item, ['accountId' => 1]));
    }

}
