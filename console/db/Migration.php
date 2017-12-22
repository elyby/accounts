<?php
namespace console\db;

use yii\db\Migration as YiiMigration;

/**
 * @property string $tableOptions
 */
class Migration extends YiiMigration {

    public function getTableOptions($engine = 'InnoDB') {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=' . $engine;
        }

        return $tableOptions;
    }

    public function createTable($table, $columns, $options = null) {
        if ($options === null) {
            $options = $this->getTableOptions();
        }

        parent::createTable($table, $columns, $options);
    }

    protected function primary(...$columns) {
        switch (count($columns)) {
            case 0:
                $key = '';
                break;
            case 1:
                $key = $columns[0];
                break;
            default:
                $key = $this->buildKey($columns);
        }

        return " PRIMARY KEY ($key) ";
    }

    private function buildKey(array $columns) {
        $key = '';
        foreach ($columns as $i => $column) {
            $key .= $i == count($columns) ? $column : "$column,";
        }

        return $key;
    }

}
