<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class RefreshTokenEntity implements RefreshTokenEntityInterface {
    use EntityTrait;
    use RefreshTokenTrait;

    /**
     * We don't rotate refresh tokens, so that to always pass validation in the internal validator
     * of the oauth2 server implementation we set the lifetime as far as possible.
     *
     * In 2038 this may cause problems, but I am sure that by then this code, if it still works,
     * will be rewritten several times and the problem will be solved in a completely different way.
     *
     * @return DateTimeImmutable
     */
    public function getExpiryDateTime(): DateTimeImmutable {
        return CarbonImmutable::create(2038, 11, 11, 22, 13, 0, 'Europe/Minsk');
    }

}
