<?php
declare(strict_types=1);

namespace common\db\mysql;

use SamIT\Yii2\MariaDb\QueryBuilder as MysqlQueryBuilder;
use yii\db\ExpressionInterface;

class QueryBuilder extends MysqlQueryBuilder {

    public function buildOrderBy($columns) {
        if (empty($columns)) {
            return '';
        }

        $orders = [];
        foreach ($columns as $name => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $orders[] = $direction->expression;
            } elseif (is_array($direction)) {
                // This condition branch is our custom solution
                if (empty($direction)) {
                    continue;
                }

                $fieldValues = [];
                foreach ($direction as $fieldValue) {
                    $fieldValues[] = $this->db->quoteValue($fieldValue);
                }

                $orders[] = 'FIELD(' . $this->db->quoteColumnName($name) . ',' . implode(',', $fieldValues) . ')';
            } else {
                $orders[] = $this->db->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

}
