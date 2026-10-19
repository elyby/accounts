<?php
declare(strict_types=1);

namespace api\tests;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha as AltchaLib;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use api\tests\_generated\FunctionalTesterActions;
use Codeception\Actor;
use common\models\Account;
use InvalidArgumentException;
use Yii;

class FunctionalTester extends Actor {
    use FunctionalTesterActions;

    public function amAuthenticated(string $asUsername = 'admin'): mixed {
        /** @var Account|null $account */
        $account = Account::findOne(['username' => $asUsername]);
        if ($account === null) {
            throw new InvalidArgumentException("Cannot find account with username \"{$asUsername}\"");
        }

        $token = Yii::$app->tokensFactory->createForWebAccount($account);
        $this->amBearerAuthenticated($token->toString());

        return $account->id;
    }

    /**
     * Requests a new ALTCHA challenge and solves it like the client side does
     *
     * @return string the payload to be passed as the captcha value
     */
    public function solveAltchaChallenge(): string {
        $this->sendGET('/api/captcha/altcha/challenge');
        $this->seeResponseCodeIs(200);
        /** @var array<string, mixed> $data */
        $data = json_decode($this->grabResponse(), true, flags: JSON_THROW_ON_ERROR);
        $challenge = Challenge::fromArray($data);
        // Solving doesn't need the secrets
        $solution = (new AltchaLib())->solveChallenge(new SolveChallengeOptions($challenge, new Pbkdf2()));
        $this->assertNotNull($solution, 'Unable to solve the ALTCHA challenge');

        return (new Payload($challenge, $solution))->toBase64();
    }

    public function notLoggedIn(): void {
        $this->haveHttpHeader('Authorization', '');
        Yii::$app->user->logout();
    }

    public function canSeeAuthCredentials($expectRefreshToken = false): void {
        $this->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $this->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        if ($expectRefreshToken) {
            $this->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
        } else {
            $this->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
        }
    }

}
