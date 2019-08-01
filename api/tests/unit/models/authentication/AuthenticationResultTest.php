<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\models\authentication\AuthenticationResult;
use api\tests\unit\TestCase;
use Lcobucci\JWT\Token;
use Yii;

class AuthenticationResultTest extends TestCase {

    public function testGetters() {
        $token = new Token();
        $model = new AuthenticationResult($token);
        $this->assertSame($token, $model->getToken());
        $this->assertNull($model->getRefreshToken());

        $model = new AuthenticationResult($token, 'refresh_token');
        $this->assertSame('refresh_token', $model->getRefreshToken());
    }

    public function testGetAsResponse() {
        $token = Yii::$app->tokens->create();
        $jwt = (string)$token;

        $model = new AuthenticationResult($token);
        $result = $model->formatAsOAuth2Response();
        $this->assertSame($jwt, $result['access_token']);
        $this->assertEqualsWithDelta(3600, $result['expires_in'], 1);
        $this->assertArrayNotHasKey('refresh_token', $result);

        $model = new AuthenticationResult($token, 'refresh_token');
        $result = $model->formatAsOAuth2Response();
        $this->assertSame($jwt, $result['access_token']);
        $this->assertEqualsWithDelta(3600, $result['expires_in'], 1);
        $this->assertSame('refresh_token', $result['refresh_token']);
    }

}
