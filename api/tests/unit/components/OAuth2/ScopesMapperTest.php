<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2;

use api\tests\unit\TestCase;
use common\components\OAuth2\ScopesMapper;

final class ScopesMapperTest extends TestCase {

    public function testToPermission(): void {
        $this->assertSame('obtain_own_account_info', ScopesMapper::toPermission('account_info'));
        $this->assertSame('obtain_account_email', ScopesMapper::toPermission('account_email'));
        $this->assertSame('obtain_account_extended_info', ScopesMapper::toPermission('internal_account_info'));
        $this->assertSame('minecraft_server_session', ScopesMapper::toPermission('minecraft_server_session'));
        $this->assertSame('obtain_own_account_info', ScopesMapper::toPermission('profile'));
        $this->assertSame('obtain_account_email', ScopesMapper::toPermission('email'));
        $this->assertSame('openid', ScopesMapper::toPermission('openid'));
        $this->assertSame('obtain_own_account_info', ScopesMapper::toPermission('obtain_own_account_info'));
    }

    public function testToScope(): void {
        $this->assertSame('account_info', ScopesMapper::toScope('obtain_own_account_info'));
        $this->assertSame('account_email', ScopesMapper::toScope('obtain_account_email'));
        $this->assertSame('internal_account_info', ScopesMapper::toScope('obtain_account_extended_info'));
        $this->assertSame('minecraft_server_session', ScopesMapper::toScope('minecraft_server_session'));
        $this->assertSame('account_info', ScopesMapper::toScope('account_info'));
        // The aliases must not affect the normalization
        $this->assertSame('profile', ScopesMapper::toScope('profile'));
        $this->assertSame('email', ScopesMapper::toScope('email'));
    }

    public function testNormalizeScopes(): void {
        $this->assertSame(
            ['minecraft_server_session', 'account_info', 'account_email'],
            ScopesMapper::normalizeScopes(['minecraft_server_session', 'obtain_own_account_info', 'account_info', 'account_email']),
        );
        $this->assertSame([], ScopesMapper::normalizeScopes([]));
        // Our own scopes are collapsed into the OpenID Connect ones with the same permissions
        $this->assertSame(
            ['openid', 'profile', 'email'],
            ScopesMapper::normalizeScopes(['openid', 'account_info', 'profile', 'obtain_account_email', 'email']),
        );
        $this->assertSame(['account_info', 'email'], ScopesMapper::normalizeScopes(['account_info', 'email']));
    }

}
