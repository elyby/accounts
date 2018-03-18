<?php

use console\db\Migration;

class m180224_132027_extend_oauth_clients_attributes extends Migration {

    public function safeUp() {
        $this->addColumn('{{%oauth_clients}}', 'type', $this->string()->notNull()->after('secret'));
        $this->addColumn('{{%oauth_clients}}', 'website_url', $this->string()->null()->after('redirect_uri'));
        $this->addColumn('{{%oauth_clients}}', 'minecraft_server_ip', $this->string()->null()->after('website_url'));
        $this->addColumn('{{%oauth_clients}}', 'is_deleted', $this->boolean()->notNull()->defaultValue(false)->after('is_trusted'));
        $this->update('{{%oauth_clients}}', [
            'type' => 'application',
        ]);
        $this->addColumn('{{%oauth_sessions}}', 'created_at', $this->integer()->unsigned()->notNull());
        $this->update('{{%oauth_sessions}}', [
            'created_at' => time(),
        ]);
    }

    public function safeDown() {
        $this->dropColumn('{{%oauth_clients}}', 'type');
        $this->dropColumn('{{%oauth_clients}}', 'website_url');
        $this->dropColumn('{{%oauth_clients}}', 'minecraft_server_ip');
        $this->dropColumn('{{%oauth_clients}}', 'is_deleted');
        $this->dropColumn('{{%oauth_sessions}}', 'created_at');
    }

}
