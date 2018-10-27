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

    protected function primary(string ...$columns): string {
        foreach ($columns as &$column) {
            $column = $this->db->quoteColumnName($column);
        }

        return ' PRIMARY KEY (' . implode(', ', $columns) . ') ';
    }

}
