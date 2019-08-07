<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * @see \common\models\OauthSession
 */
class OauthSessionQuery extends ActiveQuery {

    /**
     * The owner_id field in the oauth_sessions table has a string type.
     * If you try to search using an integer value, the MariaDB will not apply the index, which will cause
     * a huge rows scan.
     *
     * After examining the query builder logic in Yii2, we managed to find a solution to bring the value
     * that the builder will use to create a link to the string exactly before the construction
     * and restore the original value afterwards.
     *
     * @param $builder
     * @return ActiveQuery|\yii\db\Query
     */
    public function prepare($builder) {
        $idHasBeenCastedToString = false;
        if ($this->primaryModel instanceof Account && $this->link === ['owner_id' => 'id']) {
            $this->primaryModel->id = (string)$this->primaryModel->id;
            $idHasBeenCastedToString = true;
        }

        $query = parent::prepare($builder);

        if ($idHasBeenCastedToString) {
            $this->primaryModel->id = (int)$this->primaryModel->id;
        }

        return $query;
    }

}
