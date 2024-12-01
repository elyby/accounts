<?php

use console\db\Migration;

class m160201_055928_oauth extends Migration {

    public function safeUp(): void {
        $this->createTable('{{%oauth_clients}}', [
            'id' => $this->string(64),
            'secret' => $this->string()->notNull(),
            'name' => $this->string()->notNull(),
            'description' => $this->string(),
            'redirect_uri' => $this->string()->notNull(),
            'account_id' => $this->getDb()->getTableSchema('{{%accounts}}')->getColumn('id')->dbType,
            'is_trusted' => $this->boolean()->defaultValue(false)->notNull(),
            'created_at' => $this->integer()->notNull(),
            $this->primary('id'),
        ], $this->tableOptions);

        $this->createTable('{{%oauth_scopes}}', [
            'id' => $this->string(64),
            $this->primary('id'),
        ], $this->tableOptions);

        $this->createTable('{{%oauth_sessions}}', [
            'id' => $this->primaryKey(),
            'owner_type' => $this->string()->notNull(),
            'owner_id' => $this->string()->notNull(),
            'client_id' => $this->getDb()->getTableSchema('{{%oauth_clients}}')->getColumn('id')->dbType,
            'client_redirect_uri' => $this->string(),
        ], $this->tableOptions);

        $this->createTable('{{%oauth_access_tokens}}', [
            'access_token' => $this->string(64),
            'session_id' => $this->getDb()->getTableSchema('{{%oauth_sessions}}')->getColumn('id')->dbType,
            'expire_time' => $this->integer()->notNull(),
            $this->primary('access_token'),
        ], $this->tableOptions);

        $this->addForeignKey(
            'FK_oauth_client_to_accounts',
            '{{%oauth_clients}}',
            'account_id',
            '{{%accounts}}',
            'id',
            'CASCADE',
        );

        $this->addForeignKey(
            'FK_oauth_session_to_client',
            '{{%oauth_sessions}}',
            'client_id',
            '{{%oauth_clients}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->addForeignKey(
            'FK_oauth_access_toke_to_oauth_session',
            '{{%oauth_access_tokens}}',
            'session_id',
            '{{%oauth_sessions}}',
            'id',
            'CASCADE',
            'SET NULL',
        );
    }

    public function safeDown(): void {
        $this->dropTable('{{%oauth_access_tokens}}');
        $this->dropTable('{{%oauth_sessions}}');
        $this->dropTable('{{%oauth_scopes}}');
        $this->dropTable('{{%oauth_clients}}');
    }

}
