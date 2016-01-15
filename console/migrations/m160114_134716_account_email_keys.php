<?php

use console\db\Migration;

class m160114_134716_account_email_keys extends Migration {

    public function safeUp() {
        $this->createTable('{{%email_activations}}', [
            'id' => $this->primaryKey(),
            'account_id' => $this->getDb()->getTableSchema('{{%accounts}}')->getColumn('id')->dbType . ' NOT NULL',
            'key' => $this->string()->unique()->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->addForeignKey('FK_email_activation_to_account', '{{%email_activations}}', 'account_id', '{{%accounts}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown() {
        $this->dropTable('{{%email_activations}}');
    }

}
