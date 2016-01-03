<?php

use console\db\Migration;

class m130524_201442_init extends Migration {

    public function up() {
        $this->createTable('{{%accounts}}', [
            'id' => $this->primaryKey(),
            'uuid' => $this->string(36)->notNull(),
            'email' => $this->string()->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'password_hash_strategy' => $this->smallInteger()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'auth_key' => $this->string(32)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->tableOptions);
    }

    public function down() {
        $this->dropTable('{{%accounts}}');
    }

}
