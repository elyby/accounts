<?php
namespace api\tests\functional;

use api\tests\_pages\SignupRoute;
use api\tests\FunctionalTester;
use Codeception\Example;

class RegisterCest {

    private SignupRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new SignupRoute($I);
    }

    /**
     * @dataProvider getSuccessInputExamples
     */
    public function testUserCorrectRegistration(FunctionalTester $I, Example $example): void {
        $I->wantTo($example->offsetGet('case'));
        $this->route->register($example->offsetGet('request'));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => true]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
    }

    /**
     * @dataProvider getInvalidInputExamples
     */
    public function testIncorrectRegistration(FunctionalTester $I, Example $example): void {
        $I->wantTo($example->offsetGet('case'));
        $this->route->register($example->offsetGet('request'));
        if ($example->offsetExists('canSee')) {
            $I->canSeeResponseContainsJson($example->offsetGet('canSee'));
        }

        if ($example->offsetExists('cantSee')) {
            $I->cantSeeResponseContainsJson($example->offsetGet('cantSee'));
        }

        if ($example->offsetExists('shouldNotMatch')) {
            foreach ((array)$example->offsetGet('shouldNotMatch') as $jsonPath) {
                $I->cantSeeResponseJsonMatchesJsonPath($jsonPath);
            }
        }
    }

    protected function getSuccessInputExamples(): array {
        return [
            [
                'case' => 'ensure that signup works',
                'request' => [
                    'username' => 'some_username',
                    'email' => 'some_email@gmail.com',
                    'password' => 'some_password',
                    'rePassword' => 'some_password',
                    'rulesAgreement' => true,
                    'lang' => 'ru',
                ],
            ],
            [
                'case' => 'ensure that signup allow reassign not finished registration username',
                'request' => [
                    'username' => 'howe.garnett',
                    'email' => 'custom-email@gmail.com',
                    'password' => 'some_password',
                    'rePassword' => 'some_password',
                    'rulesAgreement' => true,
                    'lang' => 'ru',
                ],
            ],
            [
                'case' => 'ensure that signup allow reassign not finished registration email',
                'request' => [
                    'username' => 'CustomUsername',
                    'email' => 'achristiansen@gmail.com',
                    'password' => 'some_password',
                    'rePassword' => 'some_password',
                    'rulesAgreement' => true,
                    'lang' => 'ru',
                ],
            ],
        ];
    }

    protected function getInvalidInputExamples(): array {
        return [
            [
                'case' => 'get error.rulesAgreement_required if we don\'t accept rules',
                'request' => [
                    'username' => 'ErickSkrauch',
                    'email' => 'erickskrauch@ely.by',
                    'password' => 'some_password',
                    'rePassword' => 'some_password',
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'rulesAgreement' => 'error.rulesAgreement_required',
                    ],
                ],
            ],
            [
                'case' => 'don\'t see error.rulesAgreement_requireds if we accept rules',
                'request' => [
                    'rulesAgreement' => true,
                ],
                'cantSee' => [
                    'errors' => [
                        'rulesAgreement' => 'error.rulesAgreement_required',
                    ],
                ],
            ],
            [
                'case' => 'see error.username_required if username is not set',
                'request' => [
                    'username' => '',
                    'email' => '',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'username' => 'error.username_required',
                    ],
                ],
            ],
            [
                'case' => 'don\'t see error.username_required if username is not set',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => '',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'cantSee' => [
                    'errors' => [
                        'username' => 'error.username_required',
                    ],
                ],
            ],
            [
                'case' => 'see error.email_required if email is not set',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => '',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'email' => 'error.email_required',
                    ],
                ],
            ],
            [
                'case' => 'see error.email_invalid if email is set, but invalid',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'invalid@email',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'email' => 'error.email_invalid',
                    ],
                ],
            ],
            [
                'case' => 'see error.email_invalid if email is set, valid, but domain doesn\'t exist or don\'t have mx record',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'invalid@this-should-be-really-no-exists-domain-63efd7ab-1529-46d5-9426-fa5ed9f710e6.com',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'email' => 'error.email_invalid',
                    ],
                ],
            ],
            [
                'case' => 'see error.email_not_available if email is set, fully valid, but not available for registration',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'admin@ely.by',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'email' => 'error.email_not_available',
                    ],
                ],
            ],
            [
                'case' => 'don\'t see errors on email if email valid',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'erickskrauch@ely.by',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'shouldNotMatch' => [
                    '$.errors.email',
                ],
            ],
            [
                'case' => 'see error.password_required if password is not set',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'erickskrauch@ely.by',
                    'password' => '',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'password' => 'error.password_required',
                    ],
                ],
            ],
            [
                'case' => 'see error.password_too_short before it will be compared with rePassword',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'correct-email@ely.by',
                    'password' => 'short',
                    'rePassword' => 'password',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'password' => 'error.password_too_short',
                    ],
                ],
                'shouldNotMatch' => [
                    '$.errors.rePassword',
                ],
            ],
            [
                'case' => 'see error.rePassword_required if password valid and rePassword not set',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'correct-email@ely.by',
                    'password' => 'valid-password',
                    'rePassword' => '',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'rePassword' => 'error.rePassword_required',
                    ],
                ],
            ],
            [
                'case' => 'see error.rePassword_does_not_match if password valid and rePassword doesn\'t match it',
                'request' => [
                    'username' => 'valid_nickname',
                    'email' => 'correct-email@ely.by',
                    'password' => 'valid-password',
                    'rePassword' => 'password',
                    'rulesAgreement' => true,
                ],
                'canSee' => [
                    'success' => false,
                    'errors' => [
                        'rePassword' => 'error.rePassword_does_not_match',
                    ],
                ],
                'shouldNotMatch' => [
                    '$.errors.password',
                ],
            ],
        ];
    }

}
