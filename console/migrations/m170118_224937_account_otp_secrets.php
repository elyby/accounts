<?php

use console\db\Migration;

class m170118_224937_account_otp_secrets extends Migration {

    public function safeUp() {
        $this->addColumn('{{%accounts}}', 'otp_secret', $this->string()->after('registration_ip'));
        $this->addColumn('{{%accounts}}', 'is_otp_enabled', $this->boolean()->notNull()->defaultValue(false)->after('otp_secret'));
    }

    public function safeDown() {
        $this->dropColumn('{{%accounts}}', 'otp_secret');
        $this->dropColumn('{{%accounts}}', 'is_otp_enabled');
    }

}
