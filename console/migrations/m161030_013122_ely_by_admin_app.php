<?php

use console\db\Migration;

class m161030_013122_ely_by_admin_app extends Migration {

    public function safeUp(): void {
        $exists = $this->db->createCommand('
            SELECT COUNT(*)
              FROM {{%oauth_clients}}
             WHERE id = :app_name
             LIMIT 1
        ', [
            'app_name' => 'ely_admin',
        ])->queryScalar();

        if (!$exists) {
            $this->insert('{{%oauth_clients}}', [
                'id' => 'ely_admin',
                'secret' => 'change_this_on_production',
                'name' => 'Admin Ely.by',
                'description' => '',
                'redirect_uri' => 'http://admin.ely.by/authorization/oauth',
                'is_trusted' => 1,
                'created_at' => time(),
            ]);
        }
    }

    public function safeDown(): void {
        $this->delete('{{%oauth_clients}}', ['id' => 'ely_admin']);
    }

}
