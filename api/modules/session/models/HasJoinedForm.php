<?php
declare(strict_types=1);

namespace api\modules\session\models;

use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\models\protocols\HasJoinedInterface;
use api\modules\session\Module as Session;
use common\models\Account;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\Model;

class HasJoinedForm extends Model {

    /**
     * @var HasJoinedInterface
     */
    private $protocol;

    public function __construct(HasJoinedInterface $protocol, array $config = []) {
        parent::__construct($config);
        $this->protocol = $protocol;
    }

    /**
     * @return Account
     * @throws ForbiddenOperationException
     * @throws IllegalArgumentException
     */
    public function hasJoined(): Account {
        Yii::$app->statsd->inc('sessionserver.hasJoined.attempt');
        if (!$this->protocol->validate()) {
            throw new IllegalArgumentException();
        }

        $serverId = $this->protocol->getServerId();
        $username = $this->protocol->getUsername();

        Session::info("Server with server_id = '{$serverId}' trying to verify has joined user with username = '{$username}'.");

        $joinModel = SessionModel::find($username, $serverId);
        if ($joinModel === null) {
            Session::error("Not found join operation for username = '{$username}'.");
            Yii::$app->statsd->inc('sessionserver.hasJoined.fail_no_join');
            throw new ForbiddenOperationException('Invalid token.');
        }

        $joinModel->delete();
        /** @var Account $account */
        $account = $joinModel->getAccount();
        Assert::notNull($account);

        Session::info("User with username = '{$username}' successfully verified by server with server_id = '{$serverId}'.");
        Yii::$app->statsd->inc('sessionserver.hasJoined.success');

        return $account;
    }

}
