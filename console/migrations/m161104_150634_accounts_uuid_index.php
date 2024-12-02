<?php

use console\db\Migration;

class m161104_150634_accounts_uuid_index extends Migration {

    public function safeUp(): void {
        $this->createIndex('uuid', '{{%accounts}}', 'uuid', true);
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'uuid');
    }

}
