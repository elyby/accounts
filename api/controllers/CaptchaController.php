<?php
declare(strict_types=1);

namespace api\controllers;

use api\components\Captcha\CaptchaRegistry;
use api\components\Captcha\Providers\Altcha;
use api\filters\IpRateLimiter;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;
use yii\web\NotFoundHttpException;

final class CaptchaController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => new UnsetArrayValue(),
            // Issuing a challenge calculates the KDF once, so the public endpoint must be limited
            'rateLimiter' => [
                'class' => IpRateLimiter::class,
                'only' => ['altcha-challenge'],
                'keyPrefix' => 'captcha:altcha:challenge:ratelimit',
                'limits' => [
                    60 => 30,
                    3600 => 300,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, string[]>
     */
    public function verbs(): array {
        return [
            'altcha-challenge' => ['GET'],
        ];
    }

    public function actionAltchaChallenge(CaptchaRegistry $captchaRegistry): array {
        try {
            /** @var \api\components\Captcha\Providers\Altcha $altcha */
            $altcha = $captchaRegistry->getProvider(Altcha::NAME);
        } catch (\Throwable $e) {
            throw new NotFoundHttpException(previous: $e);
        }

        // Every response is unique, so neither nginx nor the browser must cache it
        Yii::$app->response->headers->set('Cache-Control', 'no-store');

        return $altcha->createChallenge();
    }

}
