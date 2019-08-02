<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\TokensFactory;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;

class TokensFactoryTest extends TestCase {

    public function testCreateForAccount() {
        $account = new Account();
        $account->id = 1;

        $token = TokensFactory::createForAccount($account);
        $this->assertEqualsWithDelta(time(), $token->getClaim('iat'), 1);
        $this->assertEqualsWithDelta(time() + 60 * 60 * 24 * 7, $token->getClaim('exp'), 2);
        $this->assertSame('ely|1', $token->getClaim('sub'));
        $this->assertSame('accounts_web_user', $token->getClaim('ely-scopes'));
        $this->assertArrayNotHasKey('jti', $token->getClaims());

        $session = new AccountSession();
        $session->id = 2;

        $token = TokensFactory::createForAccount($account, $session);
        $this->assertEqualsWithDelta(time(), $token->getClaim('iat'), 1);
        $this->assertEqualsWithDelta(time() + 3600, $token->getClaim('exp'), 2);
        $this->assertSame('ely|1', $token->getClaim('sub'));
        $this->assertSame('accounts_web_user', $token->getClaim('ely-scopes'));
        $this->assertSame(2, $token->getClaim('jti'));
    }

}
