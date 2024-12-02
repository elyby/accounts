<?php

use console\db\Migration;

class m160512_080955_usernames_history_encoding extends Migration {

    public function safeUp(): void {
        $this->getDb()->createCommand('
            ALTER TABLE {{%usernames_history}}
            MODIFY username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
        ')->execute();
    }

    public function safeDown(): void {
        $this->alterColumn('{{%usernames_history}}', 'username', $this->string()->notNull());
    }

}
