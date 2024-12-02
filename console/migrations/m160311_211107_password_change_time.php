<?php

use console\db\Migration;

class m160311_211107_password_change_time extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%accounts}}', 'password_changed_at', $this->integer()->notNull());
        $this->getDb()->createCommand('
            UPDATE {{%accounts}}
               SET password_changed_at = created_at
        ')->execute();
        $this->dropColumn('{{%accounts}}', 'auth_key');
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'password_changed_at');
        $this->addColumn('{{%accounts}}', 'auth_key', $this->string(32)->notNull() . ' AFTER `status`');
    }

}
