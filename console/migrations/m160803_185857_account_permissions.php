<?php

use console\db\Migration;

class m160803_185857_account_permissions extends Migration {

    public function safeUp(): void {
        $this->batchInsert('{{%oauth_scopes}}', ['id'], [
            ['account_info'],
            ['account_email'],
        ]);
    }

    public function safeDown(): void {
        $this->delete('{{%oauth_scopes}}', ['id' => ['account_info', 'account_email']]);
    }

}
