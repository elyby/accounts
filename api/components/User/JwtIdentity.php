<?php
namespace api\components\User;

use common\models\Account;
use Emarref\Jwt\Claim\Subject;
use Emarref\Jwt\Exception\ExpiredException;
use Emarref\Jwt\Token;
use Exception;
use Yii;
use yii\base\NotSupportedException;
use yii\helpers\StringHelper;
use yii\web\UnauthorizedHttpException;

class JwtIdentity implements IdentityInterface {

    /**
     * @var string
     */
    private $rawToken;

    /**
     * @var Token
     */
    private $token;

    public static function findIdentityByAccessToken($rawToken, $type = null): IdentityInterface {
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;
        try {
            $token = $component->parseToken($rawToken);
        } catch (ExpiredException $e) {
            throw new UnauthorizedHttpException('Token expired');
        } catch (Exception $e) {
            Yii::error($e);
            throw new UnauthorizedHttpException('Incorrect token');
        }

        return new self($rawToken, $token);
    }

    public function getAccount(): ?Account {
        /** @var Subject $subject */
        $subject = $this->token->getPayload()->findClaimByName(Subject::NAME);
        if ($subject === null) {
            return null;
        }

        $value = $subject->getValue();
        if (!StringHelper::startsWith($value, Component::JWT_SUBJECT_PREFIX)) {
            Yii::warning('Unknown jwt subject: ' . $value);
            return null;
        }

        $accountId = (int)mb_substr($value, mb_strlen(Component::JWT_SUBJECT_PREFIX));
        $account = Account::findOne($accountId);
        if ($account === null) {
            return null;
        }

        return $account;
    }

    public function getAssignedPermissions(): array {
        /** @var Subject $scopesClaim */
        $scopesClaim = $this->token->getPayload()->findClaimByName(ScopesClaim::NAME);
        if ($scopesClaim === null) {
            return [];
        }

        return explode(',', $scopesClaim->getValue());
    }

    public function getId(): string {
        return $this->rawToken;
    }

    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    private function __construct(string $rawToken, Token $token) {
        $this->rawToken = $rawToken;
        $this->token = $token;
    }

}
