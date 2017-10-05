<?php

use console\db\Migration;

class m170704_215436_allow_null_owner_id extends Migration {

    public function safeUp() {
        $this->alterColumn('{{%oauth_sessions}}', 'owner_id', $this->string()->null());
    }

    public function safeDown() {
        $this->delete('{{%oauth_sessions}}', ['owner_id' => null]);
        $this->alterColumn('{{%oauth_sessions}}', 'owner_id', $this->string()->notNull());
    }

}
