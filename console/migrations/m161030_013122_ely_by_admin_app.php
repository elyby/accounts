<?php

use console\db\Migration;

class m161030_013122_ely_by_admin_app extends Migration {

    const APP_NAME = 'ely_admin';

    public function safeUp() {
        $exists = $this->db->createCommand('
            SELECT COUNT(*)
              FROM {{%oauth_clients}}
             WHERE id = :app_name
             LIMIT 1
        ', [
            'app_name' => self::APP_NAME,
        ])->queryScalar();

        if (!$exists) {
            $this->insert('{{%oauth_clients}}', [
                'id' => self::APP_NAME,
                'secret' => 'change_this_on_production',
                'name' => 'Admin Ely.by',
                'description' => '',
                'redirect_uri' => 'http://admin.ely.by/authorization/oauth',
                'is_trusted' => 1,
                'created_at' => time(),
            ]);
        }
    }

    public function safeDown() {
        $this->delete('{{%oauth_clients}}', ['id' => self::APP_NAME]);
    }

}
