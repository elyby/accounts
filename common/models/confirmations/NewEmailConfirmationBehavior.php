<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\behaviors\DataBehavior;

/**
 * @property string $newEmail
 */
class NewEmailConfirmationBehavior extends DataBehavior {

    public function getNewEmail(): string {
        return $this->getKey('newEmail');
    }

    public function setNewEmail(string $newEmail): void {
        $this->setKey('newEmail', $newEmail);
    }

}
