<?php
namespace common\db\mysql;

use yii\db\ExpressionInterface;
use yii\db\mysql\QueryBuilder as MysqlQueryBuilder;

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
