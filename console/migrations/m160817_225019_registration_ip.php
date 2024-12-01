<?php

use console\db\Migration;

class m160817_225019_registration_ip extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%accounts}}', 'registration_ip', 'VARBINARY(16) AFTER rules_agreement_version');
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'registration_ip');
    }

}
