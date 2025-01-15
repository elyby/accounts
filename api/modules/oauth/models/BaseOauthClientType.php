<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\models\base\ApiForm;
use common\helpers\Error as E;
use common\models\OauthClient;

abstract class BaseOauthClientType extends ApiForm implements OauthClientTypeForm {

    public mixed $name = null;

    public mixed $websiteUrl = null;

    public function rules(): array {
        return [
            ['name', 'required', 'message' => E::NAME_REQUIRED],
            ['websiteUrl', 'url', 'message' => E::WEBSITE_URL_INVALID],
        ];
    }

    public function load($data, $formName = null): bool {
        return parent::load($data, $formName);
    }

    public function validate($attributeNames = null, $clearErrors = true): bool {
        return parent::validate($attributeNames, $clearErrors);
    }

    public function getValidationErrors(): array {
        return $this->getFirstErrors();
    }

    public function applyToClient(OauthClient $client): void {
        $client->name = $this->name;
        $client->website_url = $this->websiteUrl;
    }

}
