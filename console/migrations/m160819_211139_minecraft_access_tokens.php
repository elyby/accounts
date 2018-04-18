<?php

use console\db\Migration;

class m160819_211139_minecraft_access_tokens extends Migration {

    public function safeUp() {
        $this->createTable('{{%minecraft_access_keys}}', [
            'access_token' => $this->string(36)->notNull(),
            'client_token' => $this->string(36)->notNull(),
            'account_id' => $this->db->getTableSchema('{{%accounts}}')->getColumn('id')->dbType . ' NOT NULL',
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull(),
        ]);

        $this->addPrimaryKey('access_token', '{{%minecraft_access_keys}}', 'access_token');
        $this->addForeignKey('FK_minecraft_access_token_to_account', '{{%minecraft_access_keys}}', 'account_id', '{{%accounts}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('client_token', '{{%minecraft_access_keys}}', 'client_token', true);
    }

    public function safeDown() {
        $this->dropTable('{{%minecraft_access_keys}}');
    }

}
