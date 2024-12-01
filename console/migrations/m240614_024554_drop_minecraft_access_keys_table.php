<?php
declare(strict_types=1);

use console\db\Migration;

class m240614_024554_drop_minecraft_access_keys_table extends Migration {

    public function safeUp(): void {
        $this->dropTable('minecraft_access_keys');
    }

    public function safeDown(): void {
        $this->createTable('minecraft_access_keys', [
            'access_token' => $this->string(36)->notNull(),
            'client_token' => $this->string()->notNull(),
            'account_id' => $this->db->getTableSchema('accounts')->getColumn('id')->dbType . ' NOT NULL',
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->notNull(),
            $this->primary('access_token'),
        ]);
        $this->addForeignKey('FK_minecraft_access_token_to_account', 'minecraft_access_keys', 'account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
    }

}
