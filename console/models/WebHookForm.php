<?php
declare(strict_types=1);

namespace console\models;

use common\models\WebHook;
use common\notifications;
use Webmozart\Assert\Assert;
use yii\base\Model;

class WebHookForm extends Model {

    public $url;

    public $secret;

    public $events = [];

    private $webHook;

    public function __construct(WebHook $webHook, array $config = []) {
        parent::__construct($config);
        $this->webHook = $webHook;
        $this->url = $webHook->url;
        $this->secret = $webHook->secret;
        $this->events = (array)$webHook->events;
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

        $webHook = $this->webHook;
        $webHook->url = $this->url;
        $webHook->secret = $this->secret;
        $webHook->events = array_values($this->events); // Drop the keys order
        Assert::true($webHook->save(), 'Cannot save webhook.');

        return true;
    }

    public static function getEvents(): array {
        return [
            notifications\AccountEditNotification::getType(),
            notifications\AccountDeletedNotification::getType(),
            notifications\OAuthSessionRevokedNotification::getType(),
        ];
    }

}
