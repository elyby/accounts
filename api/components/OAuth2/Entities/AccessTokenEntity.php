<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use Yii;

class AccessTokenEntity implements AccessTokenEntityInterface {
    use EntityTrait;
    use TokenEntityTrait;

    public function toString(): string {
        return Yii::$app->tokensFactory->createForOAuthClient($this)->toString();
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void {
        // We use a general-purpose component to build JWT tokens, so there is no need to keep the key
    }
}
