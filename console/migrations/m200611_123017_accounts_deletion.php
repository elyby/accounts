<?php

use console\db\Migration;

class m200611_123017_accounts_deletion extends Migration {

    public function safeUp() {
        $this->addColumn('accounts', 'deleted_at', $this->integer(11)->unsigned());
    }

    public function safeDown() {
        $this->dropColumn('accounts', 'deleted_at');
    }

}
