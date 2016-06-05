<?php

use console\db\Migration;

class m160517_151805_account_sessions extends Migration {

    public function safeUp() {
        $this->createTable('{{%accounts_sessions}}', [
            'id' => $this->primaryKey(),
            'account_id' => $this->db->getTableSchema('{{%accounts}}')->getColumn('id')->dbType . ' NOT NULL',
            'refresh_token' => $this->string()->notNull()->unique(),
            'last_used_ip' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'last_refreshed_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->addForeignKey('FK_account_session_to_account', '{{%accounts_sessions}}', 'account_id', '{{%accounts}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown() {
        $this->dropTable('{{%accounts_sessions}}');
    }

}
