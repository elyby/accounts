<?php

use console\db\Migration;

class m160104_150157_account_extended_info extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%accounts}}', 'username', $this->string()->unique()->notNull() . ' AFTER `uuid`');
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'username');
    }

}
