<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\Repositories\ExtendedDeviceCodeRepositoryInterface;
use common\components\OAuth2\ResponseTypes\EmptyResponse;
use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\DeviceCodeGrant as BaseDeviceCodeGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property ExtendedDeviceCodeRepositoryInterface $deviceCodeRepository
 */
final class DeviceCodeGrant extends BaseDeviceCodeGrant {

    public function __construct(
        ExtendedDeviceCodeRepositoryInterface $deviceCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $deviceCodeTTL,
        string $verificationUri,
        int $retryInterval = 5,
    ) {
        parent::__construct(
            $deviceCodeRepository,
            $refreshTokenRepository,
            $deviceCodeTTL,
            $verificationUri,
            $retryInterval,
        );
    }

    public function canRespondToAuthorizationRequest(ServerRequestInterface $request): bool {
        return isset($request->getQueryParams()['user_code']);
    }

    /**
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface {
        $userCode = $this->getQueryStringParameter('user_code', $request);
        if ($userCode === null) {
            throw OAuthServerException::invalidRequest('user_code');
        }

        $deviceCode = $this->deviceCodeRepository->getDeviceCodeEntityByUserCode($userCode);
        if ($deviceCode === null) {
            throw new OAuthServerException('Unknown user code', 4, 'invalid_user_code', 401);
        }

        if ($deviceCode->getUserIdentifier() !== null) {
            throw new OAuthServerException('The user code has already been used', 6, 'used_user_code', 400);
        }

        $authorizationRequest = new AuthorizationRequest();
        $authorizationRequest->setGrantTypeId($this->getIdentifier());
        $authorizationRequest->setClient($deviceCode->getClient());
        $authorizationRequest->setScopes($deviceCode->getScopes());
        // We need the device code during the "completeAuthorizationRequest" implementation, so store it inside some unused field.
        // Perfectly the implementation must rely on the "user code" but library's implementation built on top of the "device code".
        $authorizationRequest->setCodeChallenge($deviceCode->getIdentifier());

        return $authorizationRequest;
    }

    /**
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function completeAuthorizationRequest(AuthorizationRequestInterface $authorizationRequest): ResponseTypeInterface {
        $this->completeDeviceAuthorizationRequest(
            $authorizationRequest->getCodeChallenge(),
            $authorizationRequest->getUser()->getIdentifier(),
            $authorizationRequest->isAuthorizationApproved(),
        );

        return new EmptyResponse();
    }

}
