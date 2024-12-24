<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\tests\FunctionalTester;

class IndexCest {

    public function index(FunctionalTester $I): void {
        $I->sendGet('/api/authlib-injector');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'meta' => [
                'serverName' => 'Ely.by',
                'implementationName' => 'Account Ely.by adapter for the authlib-injector library',
                'implementationVersion' => '1.0.0',
                'feature.no_mojang_namespace' => true,
                'feature.enable_profile_key' => true, 
                'links' => [
                    'homepage' => 'https://ely.by',
                    'register' => 'https://account.ely.by/register',
                ],
            ],
            'skinDomains' => ['ely.by', '.ely.by'],
            'signaturePublickey' => "-----BEGIN PUBLIC KEY-----\nMFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBANbUpVCZkMKpfvYZ08W3lumdAaYxLBnm\nUDlzHBQH3DpYef5WCO32TDU6feIJ58A0lAywgtZ4wwi2dGHOz/1hAvcCAwEAAQ==\n-----END PUBLIC KEY-----",
        ]);
        $I->canSeeHttpHeader('X-Accel-Expires');
    }

}
