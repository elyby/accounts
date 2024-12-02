<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\models\authentication\AuthenticationResult;
use api\tests\unit\TestCase;
use DateTimeImmutable;
use Lcobucci\JWT\Builder as BuilderInterface;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Signer\Key;
use Yii;

class AuthenticationResultTest extends TestCase {

    public function testGetters(): void {
        $token = (new JwtFacade())
            ->issue(
                new Blake2b(),
                Key\InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY='),
                static fn(BuilderInterface $builder, DateTimeImmutable $issuedAt): BuilderInterface => $builder,
            );
        $model = new AuthenticationResult($token);
        $this->assertSame($token, $model->getToken());
        $this->assertNull($model->getRefreshToken());

        $model = new AuthenticationResult($token, 'refresh_token');
        $this->assertSame('refresh_token', $model->getRefreshToken());
    }

    public function testGetAsResponse(): void {
        $time = time() + 3600;
        $token = Yii::$app->tokens->create(['exp' => new DateTimeImmutable("@{$time}", null)]);
        $jwt = $token->toString();

        $model = new AuthenticationResult($token);
        $result = $model->formatAsOAuth2Response();
        $this->assertSame($jwt, $result['access_token']);
        $this->assertSame(3600, $result['expires_in']);
        $this->assertArrayNotHasKey('refresh_token', $result);

        $model = new AuthenticationResult($token, 'refresh_token');
        $result = $model->formatAsOAuth2Response();
        $this->assertSame($jwt, $result['access_token']);
        $this->assertSame(3600, $result['expires_in']);
        $this->assertSame('refresh_token', $result['refresh_token']);
    }

}
