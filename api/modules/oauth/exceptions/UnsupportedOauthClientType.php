<?php
namespace api\modules\oauth\exceptions;

use Throwable;
use yii\base\Exception;

class UnsupportedOauthClientType extends Exception implements OauthException {

    public function __construct(
        private readonly string $type,
        int $code = 0,
        Throwable $previous = null,
    ) {
        parent::__construct('Unsupported oauth client type', $code, $previous);
    }

    public function getType(): string {
        return $this->type;
    }

}
