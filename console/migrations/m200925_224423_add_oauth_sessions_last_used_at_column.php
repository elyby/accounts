<?php

use console\db\Migration;
use yii\db\Expression;

class m200925_224423_add_oauth_sessions_last_used_at_column extends Migration {

    public function safeUp(): void {
        $this->addColumn('oauth_sessions', 'last_used_at', $this->integer(11)->unsigned()->notNull());
        $this->update('oauth_sessions', [
            'last_used_at' => new Expression('`created_at`'),
        ]);
    }

    public function safeDown(): void {
        $this->dropColumn('oauth_sessions', 'last_used_at');
    }

}
