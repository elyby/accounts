<?php

use console\db\Migration;

class m180102_164624_increase_minecraft_access_keys_client_token_length extends Migration {

    public function safeUp(): void {
        $this->alterColumn('{{%minecraft_access_keys}}', 'client_token', $this->string()->notNull());
    }

    public function safeDown(): void {
        $this->alterColumn('{{%minecraft_access_keys}}', 'client_token', $this->string(36)->notNull());
    }

}
