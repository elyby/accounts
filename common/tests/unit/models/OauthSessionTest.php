<?php
declare(strict_types=1);

namespace common\tests\unit\models;

use Carbon\CarbonImmutable;
use common\models\OauthSession;
use common\tests\unit\TestCase;

final class OauthSessionTest extends TestCase {

    /**
     * @dataProvider getIsRevokedForTokenIssuedAtCases
     */
    public function testIsRevokedForTokenIssuedAt(?int $revokedAt, int $issuedAt, bool $expectedResult): void {
        $session = new OauthSession();
        $session->created_at = 1000;
        $session->revoked_at = $revokedAt;

        $this->assertSame($expectedResult, $session->isRevokedForTokenIssuedAt(CarbonImmutable::createFromTimestampUTC($issuedAt)));
    }

    /**
     * @return iterable<string, array{int|null, int, bool}>
     */
    public function getIsRevokedForTokenIssuedAtCases(): iterable {
        yield 'issued after the session creation' => [null, 1500, false];
        yield 'issued at the same time as the session creation' => [null, 1000, false];
        yield 'issued before the session creation' => [null, 999, true];
        yield 'issued before the revocation' => [2000, 1500, true];
        yield 'issued at the same time as the revocation' => [2000, 2000, false];
        yield 'issued after the revocation' => [2000, 2500, false];
        yield 'issued before the session creation and before the revocation' => [2000, 500, true];
    }

}
