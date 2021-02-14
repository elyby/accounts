<?php
declare(strict_types=1);

namespace common\notifications;

use common\models\Account;

final class AccountEditNotification implements NotificationInterface {

    private Account $account;

    private array $changedAttributes;

    public function __construct(Account $account, array $changedAttributes) {
        $this->account = $account;
        $this->changedAttributes = $changedAttributes;
    }

    public static function getType(): string {
        return 'account.edit';
    }

    public function getPayloads(): array {
        return [
            'id' => $this->account->id,
            'uuid' => $this->account->uuid,
            'username' => $this->account->username,
            'email' => $this->account->email,
            'lang' => $this->account->lang,
            'isActive' => $this->account->status === Account::STATUS_ACTIVE,
            'isDeleted' => $this->account->status === Account::STATUS_DELETED,
            'registered' => date('c', (int)$this->account->created_at),
            'changedAttributes' => $this->changedAttributes,
        ];
    }

}
