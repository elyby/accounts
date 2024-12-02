<?php

use console\db\Migration;

class m160806_144759_account_rules_agreement_version extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%accounts}}', 'rules_agreement_version', $this->smallInteger()->unsigned()->after('status'));
    }

    public function safeDown(): void {
        $this->dropColumn('{{%accounts}}', 'rules_agreement_version');
    }

}
