<?php
namespace codeception\common\unit\db\mysql;

use common\db\mysql\QueryBuilder;
use tests\codeception\common\unit\TestCase;
use Yii;

class QueryBuilderTest extends TestCase {

    public function testBuildOrderByField() {
        $queryBuilder = new QueryBuilder(Yii::$app->db);
        $result = $queryBuilder->buildOrderBy(['dummy' => ['first', 'second']]);
        $this->assertEquals("ORDER BY FIELD(`dummy`,'first','second')", $result);
    }

}
