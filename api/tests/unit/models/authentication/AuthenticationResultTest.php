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
        $time = time() + 3600;
        $token = Yii::$app->tokens->create(['exp' => $time]);
        $jwt = (string)$token;

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
