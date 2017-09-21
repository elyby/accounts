<?php
namespace api\components\User;

use common\models\Account;
use common\models\AccountSession;
use Emarref\Jwt\Claim\Expiration;

class AuthenticationResult {

    /**
     * @var Account
     */
    private $account;

    /**
     * @var string
     */
    private $jwt;

    /**
     * @var AccountSession|null
     */
    private $session;

    public function __construct(Account $account, string $jwt, ?AccountSession $session) {
        $this->account = $account;
        $this->jwt = $jwt;
        $this->session = $session;
    }

    public function getAccount(): Account {
        return $this->account;
    }

    public function getJwt(): string {
        return $this->jwt;
    }

    public function getSession(): ?AccountSession {
        return $this->session;
    }

    public function getAsResponse() {
        $token = (new Jwt())->deserialize($this->getJwt());

        /** @noinspection NullPointerExceptionInspection */
        $response = [
            'access_token' => $this->getJwt(),
            'expires_in' => $token->getPayload()->findClaimByName(Expiration::NAME)->getValue() - time(),
        ];

        $session = $this->getSession();
        if ($session !== null) {
            $response['refresh_token'] = $session->refresh_token;
        }

        return $response;
    }

}
