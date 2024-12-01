<?php

use console\db\Migration;

class m161222_222520_remove_oauth_access_tokens extends Migration {

    public function safeUp(): void {
        $this->dropForeignKey('FK_oauth_access_toke_to_oauth_session', '{{%oauth_access_tokens}}');
        $this->dropTable('{{%oauth_access_tokens}}');
    }

    public function safeDown(): void {
        $this->createTable('{{%oauth_access_tokens}}', [
            'access_token' => $this->string(64),
            'session_id' => $this->getDb()->getTableSchema('{{%oauth_sessions}}')->getColumn('id')->dbType,
            'expire_time' => $this->integer()->notNull(),
            $this->primary('access_token'),
        ], $this->tableOptions);

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

}
