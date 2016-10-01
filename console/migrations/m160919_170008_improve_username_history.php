<?php

use console\db\Migration;

class m160919_170008_improve_username_history extends Migration {

    public function safeUp() {
        $this->execute('
            INSERT INTO {{%usernames_history}} (account_id, username, applied_in)
            SELECT id as account_id, username, created_at as applied_at
              FROM {{%accounts}}
        ');
        $this->createIndex('applied_in', '{{%usernames_history}}', 'applied_in');
        $this->createIndex('username', '{{%usernames_history}}', 'username');
    }

    public function safeDown() {
        $this->dropIndex('applied_in', '{{%usernames_history}}');
        $this->dropIndex('username', '{{%usernames_history}}');
        $this->execute('
            DELETE FROM {{%usernames_history}}
            WHERE id IN (
                SELECT t1.id
                  FROM (
                      SELECT id, MIN(applied_in)
                        FROM {{%usernames_history}}
                       GROUP BY account_id
                  ) t1
            )
        ');
    }

}
