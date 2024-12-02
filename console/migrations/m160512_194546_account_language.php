<?php

use console\db\Migration;

class m160512_194546_account_language extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%accounts}}', 'lang', $this->string(5)->notNull()->defaultValue('en')->after('password_hash_strategy'));
        $this->dropColumn('{{%accounts}}', 'password_reset_token');
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'lang');
        $this->addColumn('{{%accounts}}', 'password_reset_token', $this->string()->unique());
    }

}
