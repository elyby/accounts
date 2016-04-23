<?php

use console\db\Migration;

class m160414_231110_account_nicknames_history extends Migration {

    public function safeUp() {
        $this->createTable('{{%usernames_history}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
            'account_id' => $this->getDb()->getSchema()->getTableSchema('{{%accounts}}')->getColumn('id')->dbType . ' NOT NULL',
            'applied_in' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->addForeignKey('FK_usernames_history_to_account', '{{%usernames_history}}', 'account_id', '{{%accounts}}', 'id', 'CASCADE', 'CASCADE');

        $accountNicknames = $this->getDb()->createCommand('
            SELECT id,
                   username,
                   updated_at
              FROM {{%accounts}}
        ')->queryAll();

        foreach($accountNicknames as $row) {
            $this->insert('{{%usernames_history}}', [
                'username' => $row['username'],
                'account_id' => $row['id'],
                'applied_in' => $row['updated_at'],
            ]);
        }
    }

    public function safeDown() {
        $this->dropTable('{{%usernames_history}}');
    }

}
