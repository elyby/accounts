<?php

use console\db\Migration;

class m161024_234121_remove_client_token_index extends Migration {

    public function safeUp() {
        $this->dropIndex('client_token', '{{%minecraft_access_keys}}');
    }

    public function safeDown() {
        $this->createIndex('client_token', '{{%minecraft_access_keys}}', 'client_token', true);
    }

}
