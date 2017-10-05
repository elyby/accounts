<?php
namespace api\modules\session\models;

use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\models\protocols\HasJoinedInterface;
use api\modules\session\Module as Session;
use common\models\Account;
use yii\base\ErrorException;
use yii\base\Model;

class HasJoinedForm extends Model {

    private $protocol;

    public function __construct(HasJoinedInterface $protocol, array $config = []) {
        $this->protocol = $protocol;
        parent::__construct($config);
    }

    public function hasJoined(): Account {
        if (!$this->protocol->validate()) {
            throw new IllegalArgumentException();
        }

        $serverId = $this->protocol->getServerId();
        $username = $this->protocol->getUsername();

        Session::info(
            "Server with server_id = '{$serverId}' trying to verify has joined user with username = '{$username}'."
        );

        $joinModel = SessionModel::find($username, $serverId);
        if ($joinModel === null) {
            Session::error("Not found join operation for username = '{$username}'.");
            throw new ForbiddenOperationException('Invalid token.');
        }

        $joinModel->delete();
        $account = $joinModel->getAccount();
        if ($account === null) {
            throw new ErrorException('Account must exists');
        }

        Session::info(
            "User with username = '{$username}' successfully verified by server with server_id = '{$serverId}'."
        );

        return $account;
    }

}
