<?php
declare(strict_types=1);

namespace common\db\mysql;

use SamIT\Yii2\MariaDb\JsonExpressionBuilder;
use SamIT\Yii2\MariaDb\Schema as MysqlSchema;

class Schema extends MysqlSchema {

    public function createQueryBuilder() {
        $result = new QueryBuilder($this->db);
        $result->setExpressionBuilders([
            'yii\db\JsonExpression' => JsonExpressionBuilder::class,
        ]);

        return $result;
    }

}
