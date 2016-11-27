<?php

use console\db\Migration;

class m161127_145211_remove_oauth_scopes extends Migration {

    public function safeUp() {
        $this->dropTable('{{%oauth_scopes}}');
    }

    public function safeDown() {
        $this->createTable('{{%oauth_scopes}}', [
            'id' => $this->string(64),
            $this->primary('id'),
        ]);

        $this->batchInsert('{{%oauth_scopes}}', ['id'], [
            ['offline_access'],
            ['minecraft_server_session'],
            ['account_info'],
            ['account_email'],
        ]);
    }

}
