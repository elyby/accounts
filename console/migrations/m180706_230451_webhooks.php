<?php

use console\db\Migration;

class m180706_230451_webhooks extends Migration {

    public function safeUp() {
        $this->createTable('{{%webhooks}}', [
            'id' => $this->primaryKey(11)->unsigned(),
            'url' => $this->string()->notNull(),
            'secret' => $this->string(),
            'created_at' => $this->integer(11)->unsigned()->notNull(),
        ]);

        $this->createTable('{{%webhooks_events}}', [
            'webhook_id' => $this->db->getTableSchema('{{%webhooks}}')->getColumn('id')->dbType . ' NOT NULL',
            'event_type' => $this->string()->notNull(),
            $this->primary('webhook_id', 'event_type'),
        ]);
        $this->addForeignKey('FK_webhook_event_to_webhook', '{{%webhooks_events}}', 'webhook_id', 'webhooks', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown() {
        $this->dropTable('{{%webhooks_events}}');
        $this->dropTable('{{%webhooks}}');
    }

}
