<?php
namespace api\modules\oauth\exceptions;

use Throwable;
use yii\base\Exception;

class UnsupportedOauthClientType extends Exception implements OauthException {

    /**
     * @var string
     */
    private $type;

    public function __construct(string $type, int $code = 0, Throwable $previous = null) {
        parent::__construct('Unsupported oauth client type', $code, $previous);
        $this->type = $type;
    }

    public function getType(): string {
        return $this->type;
    }

}
