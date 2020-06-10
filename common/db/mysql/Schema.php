<?php
declare(strict_types=1);

namespace common\db\mysql;

use SamIT\Yii2\MariaDb\JsonExpressionBuilder;
use SamIT\Yii2\MariaDb\Schema as MariaDbSchema;
use yii\db\JsonExpression;

class Schema extends MariaDbSchema {

    public function createQueryBuilder(): QueryBuilder {
        $result = new QueryBuilder($this->db);
        $result->setExpressionBuilders([
            JsonExpression::class => JsonExpressionBuilder::class,
        ]);

        return $result;
    }

}
