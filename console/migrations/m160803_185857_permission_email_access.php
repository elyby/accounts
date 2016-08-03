<?php

use console\db\Migration;

class m160803_185857_permission_email_access extends Migration {

    public function safeUp() {
        $this->insert('{{%oauth_scopes}}', ['id' => 'account_email']);
    }

    public function safeDown() {
        $this->delete('{{%oauth_scopes}}', ['id' => 'account_email']);
    }

}
