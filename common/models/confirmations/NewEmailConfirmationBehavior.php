<?php
namespace common\models\confirmations;

use common\behaviors\DataBehavior;

/**
 * @property string $newEmail
 */
class NewEmailConfirmationBehavior extends DataBehavior {

    public function getNewEmail() : string {
        return $this->getKey('newEmail');
    }

    public function setNewEmail(string $newEmail) {
        $this->setKey('newEmail', $newEmail);
    }

}
