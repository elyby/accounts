<?php

use console\db\Migration;

class m180708_155425_extends_locale_field extends Migration {

    public function safeUp(): void {
        $this->alterColumn('{{%accounts}}', 'lang', $this->string()->notNull()->defaultValue('en'));
    }

    public function safeDown(): void {
        $this->alterColumn('{{%accounts}}', 'lang', $this->string(5)->notNull()->defaultValue('en'));
    }

}
