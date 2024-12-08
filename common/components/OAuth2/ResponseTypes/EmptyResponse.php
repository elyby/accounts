<?php
declare(strict_types=1);

namespace common\components\OAuth2\ResponseTypes;

use League\OAuth2\Server\ResponseTypes\AbstractResponseType;
use Psr\Http\Message\ResponseInterface;

final class EmptyResponse extends AbstractResponseType {

    public function generateHttpResponse(ResponseInterface $response): ResponseInterface {
        return $response;
    }

}
