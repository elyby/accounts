<?php
namespace common\tests\unit\db\mysql;

use common\db\mysql\QueryBuilder;
use common\tests\unit\TestCase;
use Yii;

class QueryBuilderTest extends TestCase {

    public function testBuildOrderByField() {
        $queryBuilder = new QueryBuilder(Yii::$app->db);
        $result = $queryBuilder->buildOrderBy(['dummy' => ['first', 'second']]);
        $this->assertEquals("ORDER BY FIELD(`dummy`,'first','second')", $result);
    }

}
