<?php
/**
* Yii bootstrap file.
* Used for enhanced IDE code autocompletion.
* Note: To avoid "Multiple Implementations" PHPStorm warning and make autocomplete faster
* exclude or "Mark as Plain Text" vendor/yiisoft/yii2/Yii.php file
*/
class Yii extends \yii\BaseYii {
    /**
    * @var BaseApplication|WebApplication|ConsoleApplication the application instance
    */
    public static $app;
}

/**
 * Class BaseApplication
 * Used for properties that are identical for both WebApplication and ConsoleApplication
 *
 * @property \yii\swiftmailer\Mailer               $mailer
 * @property \common\components\Redis\Connection   $redis
 * @property \common\components\RabbitMQ\Component $amqp
 * @property \GuzzleHttp\Client                    $guzzle
 * @property \common\components\EmailRenderer      $emailRenderer
 * @property \mito\sentry\Component                $sentry
 * @property \api\components\OAuth2\Component      $oauth
 */
abstract class BaseApplication extends yii\base\Application {
}

/**
 * Class WebApplication
 * Include only Web application related components here
 *
 * @property \api\components\User\Component      $user User component.
 * @property \api\components\ReCaptcha\Component $reCaptcha
 *
 * @method \api\components\User\Component getUser()
 */
class WebApplication extends yii\web\Application {
}

/**
 * Class ConsoleApplication
 * Include only Console application related components here
 */
class ConsoleApplication extends yii\console\Application {
}
