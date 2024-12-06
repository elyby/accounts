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
 * @property \yii\db\Connection               $unbufferedDb
 * @property \yii\symfonymailer\Mailer        $mailer
 * @property \yii\redis\Connection            $redis
 * @property \GuzzleHttp\Client               $guzzle
 * @property \common\components\EmailsRenderer\Component $emailsRenderer
 * @property \nohnaimer\sentry\Component      $sentry
 * @property \common\components\StatsD        $statsd
 * @property \yii\queue\Queue                 $queue
 */
abstract class BaseApplication extends yii\base\Application {
}

/**
 * Class WebApplication
 * Include only Web application related components here
 *
 * @property \api\components\User\Component       $user
 * @property \api\components\ReCaptcha\Component  $reCaptcha
 * @property \api\components\Tokens\Component     $tokens
 * @property \api\components\Tokens\TokensFactory $tokensFactory
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
