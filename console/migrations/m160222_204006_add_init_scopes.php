<?php

use console\db\Migration;

class m160222_204006_add_init_scopes extends Migration {

    public function safeUp(): void {
        $this->batchInsert('{{%oauth_scopes}}', ['id'], [
            ['offline_access'],
            ['minecraft_server_session'],
        ]);
    }

    public function safeDown(): void {
        $this->delete('{{%oauth_scopes}}');
    }

}
