<?php
declare(strict_types=1);

namespace console\db;

use yii\db\Exception;
use yii\db\Migration as YiiMigration;

/**
 * @property string $tableOptions
 * @method \SamIT\Yii2\MariaDb\ColumnSchemaBuilder json()
 */
class Migration extends YiiMigration {

    public function getTableOptions(string $engine = 'InnoDB'): string {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=' . $engine;
        }

        return $tableOptions;
    }

    /**
     * @param array<string|\yii\db\ColumnSchemaBuilder>|null $columns
     */
    public function createTable($table, $columns, $options = null): void {
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

    protected function getPrimaryKeyType(string $table, bool $nullable = false): string {
        $primaryKeys = $this->db->getTableSchema($table)->primaryKey;
        if (count($primaryKeys) === 0) {
            throw new Exception("The table \"{$table}\" have no primary keys.");
        }

        if (count($primaryKeys) > 1) {
            throw new Exception("The table \"{$table}\" have more than one primary key.");
        }

        return $this->getColumnType($table, $primaryKeys[0], $nullable);
    }

    protected function getColumnType(string $table, string $column, bool $nullable = false): string {
        return $this->db->getTableSchema($table)->getColumn($column)->dbType . ($nullable ? '' : ' NOT NULL');
    }

}
