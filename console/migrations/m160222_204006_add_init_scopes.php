<?php

use console\db\Migration;

class m160222_204006_add_init_scopes extends Migration {

    public function safeUp() {
        $this->batchInsert('{{%oauth_scopes}}', ['id'], [
            ['offline_access'],
            ['minecraft_server_session'],
        ]);
    }

    public function safeDown() {
        $this->delete('{{%oauth_scopes}}');
    }

}
