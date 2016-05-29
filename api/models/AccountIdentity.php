<?php
namespace api\models;

use common\models\Account;
use Emarref\Jwt\Encryption\Factory;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Verification\Context as VerificationContext;
use Yii;
use yii\base\NotSupportedException;
use yii\helpers\StringHelper;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

class AccountIdentity extends Account implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $jwt = new Jwt();
        $token = $jwt->deserialize($token);
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;

        $hostInfo = Yii::$app->request->hostInfo;
        $context = new VerificationContext(Factory::create($component->getAlgorithm()));
        $context->setAudience($hostInfo);
        $context->setIssuer($hostInfo);
        try {
            $jwt->verify($token, $context);
        } catch (VerificationException $e) {
            if (StringHelper::startsWith($e->getMessage(), 'Token expired at')) {
                $message = 'Token expired';
            } else {
                $message = 'Incorrect token';
            }

            throw new UnauthorizedHttpException($message);
        }

        // Если исключение выше не случилось, то значит всё оке
        /** @var \Emarref\Jwt\Claim\JwtId $jti */
        $jti = $token->getPayload()->findClaimByName('jti');
        $account = static::findOne($jti->getValue());
        if ($account === null) {
            throw new UnauthorizedHttpException('Invalid token');
        }

        return $account;
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using JWT tokens');
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using JWT tokens');
    }

}
