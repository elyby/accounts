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
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=' . $engine;
        }

        return $tableOptions;
    }

}
