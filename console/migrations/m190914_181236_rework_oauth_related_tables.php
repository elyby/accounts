<?php
declare(strict_types=1);

use console\db\Migration;

class m190914_181236_rework_oauth_related_tables extends Migration {

    public function safeUp() {
        $this->delete('oauth_sessions', ['NOT', ['owner_type' => 'user']]);
        $this->dropColumn('oauth_sessions', 'owner_type');
        $this->dropColumn('oauth_sessions', 'client_redirect_uri');
        $this->execute('
            DELETE os1
              FROM oauth_sessions os1,
                   oauth_sessions os2
             WHERE os1.id > os2.id
               AND os1.owner_id = os2.owner_id
               AND os1.client_id = os2.client_id
        ');
        $this->dropIndex('owner_id', 'oauth_sessions');
        $this->renameColumn('oauth_sessions', 'owner_id', 'account_id');
        $this->alterColumn('oauth_sessions', 'account_id', $this->db->getTableSchema('accounts')->getColumn('id')->dbType . ' NOT NULL');
        $this->alterColumn('oauth_sessions', 'client_id', $this->db->getTableSchema('oauth_clients')->getColumn('id')->dbType . ' NOT NULL');
        // Change type to be able to remove primary key
        $this->alterColumn('oauth_sessions', 'id', $this->integer(11)->unsigned()->after('client_id'));
        $this->dropPrimaryKey('PRIMARY', 'oauth_sessions');
        // Change type again to make column nullable
        $this->alterColumn('oauth_sessions', 'id', $this->integer(11)->unsigned()->after('client_id'));
        $this->renameColumn('oauth_sessions', 'id', 'legacy_id');
        $this->createIndex('legacy_id', 'oauth_sessions', 'legacy_id', true);
        $this->addPrimaryKey('id', 'oauth_sessions', ['account_id', 'client_id']);
        $this->dropForeignKey('FK_oauth_session_to_client', 'oauth_sessions');
        $this->dropIndex('FK_oauth_session_to_client', 'oauth_sessions');
        $this->addForeignKey('FK_oauth_session_to_account', 'oauth_sessions', 'account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('FK_oauth_session_to_oauth_client', 'oauth_sessions', 'client_id', 'oauth_clients', 'id', 'CASCADE', 'CASCADE');
        $this->addColumn('oauth_sessions', 'scopes', $this->json()->toString('scopes') . ' AFTER `legacy_id`');
        $this->addColumn('oauth_sessions', 'revoked_at', $this->integer(11)->unsigned() . ' AFTER `created_at`');

        $this->insert('oauth_clients', [
            'id' => 'unauthorized_minecraft_game_launcher',
            'secret' => 'there_is_no_secret',
            'type' => 'minecraft-game-launcher',
            'name' => 'Unauthorized Minecraft game launcher',
            'created_at' => time(),
        ]);
    }

    public function safeDown() {
        $this->delete('oauth_clients', ['id' => 'unauthorized_minecraft_game_launcher']);

        $this->dropColumn('oauth_sessions', 'revoked_at');
        $this->dropColumn('oauth_sessions', 'scopes');
        $this->dropForeignKey('FK_oauth_session_to_oauth_client', 'oauth_sessions');
        $this->dropForeignKey('FK_oauth_session_to_account', 'oauth_sessions');
        $this->dropIndex('FK_oauth_session_to_oauth_client', 'oauth_sessions');
        $this->dropPrimaryKey('PRIMARY', 'oauth_sessions');
        $this->delete('oauth_sessions', ['legacy_id' => null]);
        $this->dropIndex('legacy_id', 'oauth_sessions');
        $this->alterColumn('oauth_sessions', 'legacy_id', $this->integer(11)->unsigned()->notNull()->append('AUTO_INCREMENT PRIMARY KEY FIRST'));
        $this->renameColumn('oauth_sessions', 'legacy_id', 'id');
        $this->alterColumn('oauth_sessions', 'client_id', $this->db->getTableSchema('oauth_clients')->getColumn('id')->dbType);
        $this->alterColumn('oauth_sessions', 'account_id', $this->string());
        $this->renameColumn('oauth_sessions', 'account_id', 'owner_id');
        $this->createIndex('owner_id', 'oauth_sessions', 'owner_id');
        $this->addColumn('oauth_sessions', 'owner_type', $this->string()->notNull()->after('id'));
        $this->update('oauth_sessions', ['owner_type' => 'user']);
        $this->addColumn('oauth_sessions', 'client_redirect_uri', $this->string()->after('client_id'));
        $this->addForeignKey('FK_oauth_session_to_client', 'oauth_sessions', 'client_id', 'oauth_clients', 'id', 'CASCADE', 'CASCADE');
    }

}
