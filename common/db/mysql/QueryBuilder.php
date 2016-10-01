<?php
namespace common\db\mysql;

use yii\db\Expression;
use yii\db\mysql\QueryBuilder as MysqlQueryBuilder;

class QueryBuilder extends MysqlQueryBuilder {

    public function buildOrderBy($columns) {
        if (empty($columns)) {
            return '';
        }

        $orders = [];
        foreach($columns as $name => $direction) {
            if ($direction instanceof Expression) {
                $orders[] = $direction->expression;
            } elseif (is_array($direction)) {
                // This is new feature
                if (empty($direction)) {
                    continue;
                }

                $fieldValues = [];
                foreach($direction as $fieldValue) {
                    $fieldValues[] = $this->db->quoteValue($fieldValue);
                }

                $orders[] = 'FIELD(' . $this->db->quoteColumnName($name) . ',' . implode(',', $fieldValues) . ')';
                // End of new feature
            } else {
                $orders[] = $this->db->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

}
