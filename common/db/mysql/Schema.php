<?php
namespace common\db\mysql;

use yii\db\mysql\Schema as MysqlSchema;

class Schema extends MysqlSchema {

    public function createQueryBuilder() {
        return new QueryBuilder($this->db);
    }

}
