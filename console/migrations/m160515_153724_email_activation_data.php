<?php

use console\db\Migration;

class m160515_153724_email_activation_data extends Migration {

    public function safeUp(): void {
        $this->addColumn('{{%email_activations}}', '_data', $this->text()->after('type'));
    }

    public function safeDown(): void {
        $this->dropColumn('{{%email_activations}}', '_data');
    }

}
