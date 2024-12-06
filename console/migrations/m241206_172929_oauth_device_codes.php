<?php
declare(strict_types=1);

use console\db\Migration;

class m241206_172929_oauth_device_codes extends Migration {

    public function safeUp(): void {
        $this->createTable('oauth_device_codes', [
            'device_code' => $this->string(96)->notNull(),
            'user_code' => $this->string(16)->notNull(),
            'client_id' => $this->getPrimaryKeyType('oauth_clients'),
            'scopes' => $this->json()->notNull()->toString('scopes'),
            'account_id' => $this->getPrimaryKeyType('accounts', true),
            'is_approved' => $this->boolean()->unsigned(),
            'last_polled_at' => $this->integer(11)->unsigned(),
            'expires_at' => $this->integer(11)->unsigned()->notNull(),
            $this->primary('device_code'),
        ]);
        $this->createIndex('user_code', 'oauth_device_codes', 'user_code', true);
        $this->createIndex('expires_in', 'oauth_device_codes', 'expires_at');
        $this->addForeignKey('FK_oauth_device_code_to_oauth_client', 'oauth_device_codes', 'client_id', 'oauth_clients', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('FK_oauth_device_code_to_account', 'oauth_device_codes', 'account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
        $this->execute('
            CREATE EVENT oauth_device_codes_cleanup
                      ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 1 HOUR
                      DO DELETE FROM oauth_device_codes WHERE expires_at < UNIX_TIMESTAMP()
        ');
    }

    public function safeDown(): void {
        $this->execute('DROP EVENT oauth_device_codes_cleanup');
        $this->dropTable('oauth_device_codes');
    }

}
