<?php

use console\db\Migration;

class m200613_204832_remove_webhooks_events_table extends Migration {

    public function safeUp() {
        $this->addColumn('webhooks', 'events', $this->json()->toString('events') . ' AFTER `secret`');
        $webhooksIds = $this->db->createCommand('SELECT id FROM webhooks')->queryColumn();
        foreach ($webhooksIds as $webhookId) {
            $events = $this->db->createCommand("SELECT event_type FROM webhooks_events WHERE webhook_id = {$webhookId}")->queryColumn();
            if (empty($events)) {
                continue;
            }

            $this->execute('UPDATE webhooks SET events = JSON_ARRAY("' . implode('","', $events) . '")');
        }

        $this->dropTable('webhooks_events');
    }

    public function safeDown() {
        $this->createTable('webhooks_events', [
            'webhook_id' => $this->db->getTableSchema('webhooks')->getColumn('id')->dbType . ' NOT NULL',
            'event_type' => $this->string()->notNull(),
            $this->primary('webhook_id', 'event_type'),
        ]);
        $this->addForeignKey('FK_webhook_event_to_webhook', 'webhooks_events', 'webhook_id', 'webhooks', 'id', 'CASCADE', 'CASCADE');

        $webhooks = $this->db->createCommand('SELECT id, `events` FROM webhooks')->queryAll();
        foreach ($webhooks as $webhook) {
            if (empty($webhook['events'])) {
                continue;
            }

            $events = json_decode($webhook['events'], true);
            if (empty($events)) {
                continue;
            }

            $this->batchInsert(
                'webhooks_events',
                ['webhook_id', 'event_type'],
                array_map(fn($event) => [$webhook['id'], $event], $events),
            );
        }

        $this->dropColumn('webhooks', 'events');
    }

}
