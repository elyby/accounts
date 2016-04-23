<?php

use console\db\Migration;

class m160422_230911_mojang_account_collisions extends Migration {

    public function safeUp() {
        $this->createTable('{{%mojang_usernames}}', [
            'username' => $this->string()->notNull(),
            'uuid' => $this->string(32)->notNull(),
            'last_pulled_at' => $this->integer()->unsigned()->notNull(),
        ], $this->tableOptions);

        $this->addPrimaryKey('username', '{{%mojang_usernames}}', 'username');
    }

    public function safeDown() {
        $this->dropTable('{{%mojang_usernames}}');
    }

}
