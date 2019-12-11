<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\models\base\ApiForm;
use common\helpers\Error as E;
use common\models\AccountSession;
use Webmozart\Assert\Assert;
use Yii;

class RefreshTokenForm extends ApiForm {

    public $refresh_token;

    /**
     * @var AccountSession|null
     */
    private $session;

    public function rules(): array {
        return [
            ['refresh_token', 'required', 'message' => E::REFRESH_TOKEN_REQUIRED],
            ['refresh_token', 'validateRefreshToken'],
        ];
    }

    public function validateRefreshToken(): void {
        if (!$this->hasErrors() && $this->findSession() === null) {
            $this->addError('refresh_token', E::REFRESH_TOKEN_NOT_EXISTS);
        }
    }

    /**
     * @CollectModelMetrics(prefix="authentication.renew")
     */
    public function renew(): ?AuthenticationResult {
        if (!$this->validate()) {
            return null;
        }

        /** @var AccountSession $session */
        $session = $this->findSession();
        $account = $session->account;

        $transaction = Yii::$app->db->beginTransaction();

        $token = Yii::$app->tokensFactory->createForWebAccount($account, $session);

        $session->setIp(Yii::$app->request->userIP);
        $session->touch('last_refreshed_at');
        Assert::true($session->save(), 'Cannot update session info');

        $transaction->commit();

        return new AuthenticationResult($token, $session->refresh_token);
    }

    private function findSession(): ?AccountSession {
        if ($this->session === null) {
            $this->session = AccountSession::findOne(['refresh_token' => $this->refresh_token]);
        }

        return $this->session;
    }

}
