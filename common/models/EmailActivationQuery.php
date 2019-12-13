<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * @see EmailActivation
 */
class EmailActivationQuery extends ActiveQuery {

    public function withType(int ...$typeId): self {
        return $this->andWhere(['type' => $typeId]);
    }

}
