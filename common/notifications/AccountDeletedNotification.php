<?php
declare(strict_types=1);

namespace common\notifications;

use common\models\Account;
use Webmozart\Assert\Assert;

final readonly class AccountDeletedNotification implements NotificationInterface {

    private Account $account;

    public function __construct(Account $account) {
        Assert::notNull($account->deleted_at, 'Account must be deleted');
        $this->account = $account;
    }

    public static function getType(): string {
        return 'account.deletion';
    }

    public function getPayloads(): array {
        return [
            'id' => $this->account->id,
            'uuid' => $this->account->uuid,
            'username' => $this->account->username,
            'email' => $this->account->email,
            'registered' => date('c', $this->account->created_at),
            'deleted' => date('c', $this->account->deleted_at),
        ];
    }

}
