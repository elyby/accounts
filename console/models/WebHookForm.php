<?php
declare(strict_types=1);

namespace console\models;

use api\exceptions\ThisShouldNotHappenException;
use common\models\WebHook;
use common\models\WebHookEvent;
use Yii;
use yii\base\Model;

class WebHookForm extends Model {

    public $url;

    public $secret;

    public $events = [];

    private $webHook;

    public function __construct(WebHook $webHook, array $config = []) {
        parent::__construct($config);
        $this->webHook = $webHook;
    }

    public function rules(): array {
        return [
            [['url'], 'required'],
            [['url'], 'url'],
            [['secret'], 'string'],
            [['events'], 'in', 'range' => static::getEvents(), 'allowArray' => true],
        ];
    }

    public function save(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $webHook = $this->webHook;
        $webHook->url = $this->url;
        $webHook->secret = $this->secret;
        if (!$webHook->save()) {
            throw new ThisShouldNotHappenException('Cannot save webhook.');
        }

        foreach ($this->events as $event) {
            $eventModel = new WebHookEvent();
            $eventModel->webhook_id = $webHook->id;
            $eventModel->event_type = $event;
            if (!$eventModel->save()) {
                throw new ThisShouldNotHappenException('Cannot save webhook event.');
            }
        }

        $transaction->commit();

        return true;
    }

    public static function getEvents(): array {
        return [
            'account.edit',
        ];
    }

}
