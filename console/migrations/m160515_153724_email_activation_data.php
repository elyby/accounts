<?php

use console\db\Migration;

class m160515_153724_email_activation_data extends Migration {

    public function safeUp() {
        $this->addColumn('{{%email_activations}}', '_data', $this->text()->after('type'));
    }

    public function safeDown() {
        $this->dropColumn('{{%email_activations}}', '_data');
    }

}
