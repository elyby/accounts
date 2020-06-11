<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * @see Account
 */
class AccountQuery extends ActiveQuery {

    public function excludeDeleted(): self {
        return $this->andWhere(['NOT', ['status' => Account::STATUS_DELETED]]);
    }

    public function andWhereLogin(string $login): self {
        return $this->andWhere([$this->getLoginAttribute($login) => $login]);
    }

    private function getLoginAttribute(string $login): string {
        return strpos($login, '@') ? 'email' : 'username';
    }

}
