<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use common\models\OauthClient;
use yii\helpers\ArrayHelper;

final class DesktopApplicationType extends BaseOauthClientType {

    public mixed $description = null;

    public function rules(): array {
        return ArrayHelper::merge(parent::rules(), [
            ['description', 'string'],
        ]);
    }

    public function applyToClient(OauthClient $client): void {
        parent::applyToClient($client);
        $client->description = $this->description;
    }

}
