<?php
namespace api\modules\session\models\protocols;

use yii\validators\RequiredValidator;

class ModernJoin extends BaseJoin {

    private $accessToken;
    private $selectedProfile;
    private $serverId;

    public function __construct(string $accessToken, string $selectedProfile, string $serverId) {
        $this->accessToken = $accessToken;
        $this->selectedProfile = $selectedProfile;
        $this->serverId = $serverId;
    }

    public function getAccessToken() : string {
        return $this->accessToken;
    }

    public function getSelectedProfile() : string {
        return $this->selectedProfile;
    }

    public function getServerId() : string {
        return $this->serverId;
    }

    public function validate() : bool {
        $validator = new RequiredValidator();

        return $validator->validate($this->accessToken)
            && $validator->validate($this->selectedProfile)
            && $validator->validate($this->serverId);
    }

}
