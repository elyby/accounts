<?php

use console\db\Migration;

class m200611_123017_accounts_deletion extends Migration {

    public function safeUp(): void {
        $this->addColumn('accounts', 'deleted_at', $this->integer(11)->unsigned());
    }

    public function safeDown(): void {
        $this->dropColumn('accounts', 'deleted_at');
    }

}
