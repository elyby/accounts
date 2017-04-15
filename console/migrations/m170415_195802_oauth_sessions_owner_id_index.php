<?php

use console\db\Migration;

class m170415_195802_oauth_sessions_owner_id_index extends Migration {

    public function safeUp() {
        $this->createIndex('owner_id', '{{%oauth_sessions}}', 'owner_id');
    }

    public function safeDown() {
        $this->dropIndex('owner_id', '{{%oauth_sessions}}');
    }

}
