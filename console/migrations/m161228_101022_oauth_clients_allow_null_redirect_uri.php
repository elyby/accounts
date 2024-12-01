<?php

use console\db\Migration;

class m161228_101022_oauth_clients_allow_null_redirect_uri extends Migration {

    public function safeUp(): void {
        $this->alterColumn('{{%oauth_clients}}', 'redirect_uri', $this->string());
    }

    public function safeDown(): void {
        $this->alterColumn('{{%oauth_clients}}', 'redirect_uri', $this->string()->notNull());
    }

}
