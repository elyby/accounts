<?php

use console\db\Migration;

class m171222_200114_migrate_to_utf8md4_unicode_ci extends Migration {

    public function safeUp() {
        $this->execute('SET FOREIGN_KEY_CHECKS=0');

        $dbName = $this->db->createCommand('SELECT DATABASE()')->queryScalar();
        $this->execute("ALTER DATABASE {{%$dbName}} CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
        $tables = $this->db->createCommand('SHOW TABLES')->queryColumn();
        foreach ($tables as $table) {
            $this->execute("ALTER TABLE {{%$table}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $this->execute('ALTER TABLE {{%usernames_history}} MODIFY username VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');

        $this->execute('SET FOREIGN_KEY_CHECKS=1');
    }

    public function safeDown() {
        $this->execute('SET FOREIGN_KEY_CHECKS=0');

        $dbName = $this->db->createCommand('SELECT DATABASE()')->queryScalar();
        $this->execute("ALTER DATABASE {{%$dbName}} CHARACTER SET = utf8 COLLATE = utf8_general_ci");
        $tables = $this->db->createCommand('SHOW TABLES')->queryColumn();
        foreach ($tables as $table) {
            $this->execute("ALTER TABLE {{%$table}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
        }

        $this->execute('ALTER TABLE {{%usernames_history}} MODIFY username VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL');

        $this->execute('SET FOREIGN_KEY_CHECKS=1');
    }

}
