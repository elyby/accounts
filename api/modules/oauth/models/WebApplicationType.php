<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use common\helpers\Error as E;
use common\models\OauthClient;
use yii\helpers\ArrayHelper;

final class WebApplicationType extends BaseOauthClientType {

    public mixed $description = null;

    public mixed $redirectUri = null;

    public function rules(): array {
        return ArrayHelper::merge(parent::rules(), [
            ['redirectUri', 'required', 'message' => E::REDIRECT_URI_REQUIRED],
            ['redirectUri', $this->validateUrl(...)],
            ['description', 'string'],
        ]);
    }

    public function applyToClient(OauthClient $client): void {
        parent::applyToClient($client);
        $client->description = $this->description;
        $client->redirect_uri = $this->redirectUri;
    }

    private function validateUrl(string $attribute): void {
        if (!filter_var($this->$attribute, FILTER_VALIDATE_URL)) {
            $this->addError($attribute, E::REDIRECT_URI_INVALID);
        }
    }

}
