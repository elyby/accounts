<?php

use console\db\Migration;

class m160118_184027_email_activations_code_as_primary_key extends Migration {

    public function safeUp(): void {
        $this->dropColumn('{{%email_activations}}', 'id');
        $this->dropIndex('key', '{{%email_activations}}');
        $this->alterColumn('{{%email_activations}}', 'key', $this->string()->notNull() . ' FIRST');
        $this->addPrimaryKey('key', '{{%email_activations}}', 'key');
    }

    public function safeDown(): void {
        $this->dropPrimaryKey('key', '{{%email_activations}}');
        $this->addColumn('{{%email_activations}}', 'id', $this->primaryKey() . ' FIRST');
        $this->alterColumn('{{%email_activations}}', 'key', $this->string()->unique()->notNull() . ' AFTER `id`');
    }

}
