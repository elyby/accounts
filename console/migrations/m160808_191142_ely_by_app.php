<?php

use console\db\Migration;

class m160808_191142_ely_by_app extends Migration {

    public function safeUp() {
        $exists = $this->db->createCommand('
            SELECT COUNT(*)
              FROM {{%oauth_clients}}
             WHERE id = "ely"
             LIMIT 1
        ')->queryScalar();

        if (!$exists) {
            $this->insert('{{%oauth_clients}}', [
                'id' => 'ely',
                'secret' => 'change_this_on_production',
                'name' => 'Ely.by',
                'description' => '',
                'redirect_uri' => 'http://ely.by/authorization/oauth',
                'is_trusted' => 1,
                'created_at' => time(),
            ]);
        }
    }

    public function safeDown() {
        $this->delete('{{%oauth_clients}}', ['id' => 'ely']);
    }

}
