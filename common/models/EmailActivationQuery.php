<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * @extends \yii\db\ActiveQuery<\common\models\EmailActivation>
 * @see EmailActivation
 */
final class EmailActivationQuery extends ActiveQuery {

    public function withType(int ...$typeId): self {
        return $this->andWhere(['type' => $typeId]);
    }

}
