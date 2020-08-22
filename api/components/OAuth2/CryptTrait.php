<?php
declare(strict_types=1);

namespace api\components\OAuth2;

use LogicException;
use RangeException;
use SodiumException;
use Yii;

/**
 * This trait is intended to override the standard data encryption behavior
 * with the help of \Defuse\Crypto\Crypto class, because the resultant string
 * is much larger than the original one.
 *
 * The implementation under the hood relies on using libsodium library
 * that provides more compact result values.
 */
trait CryptTrait {

    protected function encrypt($unencryptedData): string {
        return Yii::$app->tokens->encryptValue($unencryptedData);
    }

    protected function decrypt($encryptedData): string {
        try {
            return Yii::$app->tokens->decryptValue($encryptedData);
        } catch (SodiumException | RangeException $e) {
            throw new LogicException($e->getMessage(), 0, $e);
        }
    }

}
