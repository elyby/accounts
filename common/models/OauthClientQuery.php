<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\Command;

/**
 * @see OauthClient
 */
class OauthClientQuery extends ActiveQuery {

    private $showDeleted = false;

    public function includeDeleted(): self {
        $this->showDeleted = true;
        return $this;
    }

    public function onlyDeleted(): self {
        $this->showDeleted = true;
        return $this->andWhere(['is_deleted' => true]);
    }

    public function createCommand($db = null): Command {
        if ($this->showDeleted === false) {
            $this->andWhere(['is_deleted' => false]);
        }

        return parent::createCommand($db);
    }

}
