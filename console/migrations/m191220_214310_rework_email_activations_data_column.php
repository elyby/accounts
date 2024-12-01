<?php

use console\db\Migration;
use yii\db\Expression;

class m191220_214310_rework_email_activations_data_column extends Migration {

    public function safeUp(): void {
        $this->addColumn('email_activations', 'data', $this->json()->toString('data') . ' AFTER `_data`');
        $rows = $this->db->createCommand('
            SELECT `key`, `_data`
              FROM email_activations
             WHERE `_data` IS NOT NULL
          ')->queryAll();
        foreach ($rows as $row) {
            $this->update('email_activations', [
                'data' => new Expression("'" . json_encode(unserialize($row['_data'])) . "'"),
            ], [
                'key' => $row['key'],
            ]);
        }

        $this->dropColumn('email_activations', '_data');
    }

    public function safeDown(): void {
        $this->addColumn('email_activations', '_data', $this->text()->after('type'));
        $rows = $this->db->createCommand('
            SELECT `key`, `data`
              FROM email_activations
             WHERE `data` IS NOT NULL
          ')->queryAll();
        foreach ($rows as $row) {
            $this->update('email_activations', [
                '_data' => serialize(json_decode($row['data'], true)),
            ], [
                'key' => $row['key'],
            ]);
        }

        $this->dropColumn('email_activations', 'data');
    }

}
