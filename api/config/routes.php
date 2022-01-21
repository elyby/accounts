<?php
return [
    // Oauth module routes
    '/oauth2/v1/<action>' => 'oauth/authorization/<action>',
    'POST /v1/oauth2/<type>' => 'oauth/clients/create',
    'GET /v1/oauth2/<clientId>' => 'oauth/clients/get',
    'PUT /v1/oauth2/<clientId>' => 'oauth/clients/update',
    'DELETE /v1/oauth2/<clientId>' => 'oauth/clients/delete',
    'POST /v1/oauth2/<clientId>/reset' => 'oauth/clients/reset',
    'GET /v1/accounts/<accountId:\d+>/oauth2/clients' => 'oauth/clients/get-per-account',
    'GET /v1/accounts/<accountId:\d+>/oauth2/authorized' => 'oauth/clients/get-authorized-clients',
    'DELETE /v1/accounts/<accountId:\d+>/oauth2/authorized/<clientId>' => 'oauth/clients/revoke-client',
    '/account/v1/info' => 'oauth/identity/index',

    // Accounts module routes
    'GET /v1/accounts/<id:\d+>' => 'accounts/default/get',
    'DELETE /v1/accounts/<id:\d+>' => 'accounts/default/delete',
    'GET /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/get-two-factor-auth-credentials',
    'POST /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/enable-two-factor-auth',
    'DELETE /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/disable-two-factor-auth',
    'POST /v1/accounts/<id:\d+>/ban' => 'accounts/default/ban',
    'DELETE /v1/accounts/<id:\d+>/ban' => 'accounts/default/pardon',
    '/v1/accounts/<id:\d+>/<action>' => 'accounts/default/<action>',

    // Legacy accounts endpoints. It should be removed after frontend will be updated.
    'GET /accounts/current' => 'accounts/default/get',
    'POST /accounts/change-username' => 'accounts/default/username',
    'POST /accounts/change-password' => 'accounts/default/password',
    'POST /accounts/change-email/initialize' => 'accounts/default/email-verification',
    'POST /accounts/change-email/submit-new-email' => 'accounts/default/new-email-verification',
    'POST /accounts/change-email/confirm-new-email' => 'accounts/default/email',
    'POST /accounts/accept-rules' => 'accounts/default/rules',
    'GET /two-factor-auth' => 'accounts/default/get-two-factor-auth-credentials',
    'POST /two-factor-auth' => 'accounts/default/enable-two-factor-auth',
    'DELETE /two-factor-auth' => 'accounts/default/disable-two-factor-auth',
    'POST /accounts/change-lang' => 'accounts/default/language',

    // Session server module routes
    '/minecraft/session/join' => 'session/session/join',
    '/minecraft/session/legacy/join' => 'session/session/join-legacy',
    '/minecraft/session/hasJoined' => 'session/session/has-joined',
    '/minecraft/session/legacy/hasJoined' => 'session/session/has-joined-legacy',
    '/minecraft/session/profile/<uuid>' => 'session/session/profile',

    // Mojang API module routes
    '/mojang/profiles/<username>' => 'mojang/api/uuid-by-username',
    '/mojang/profiles/<uuid>/names' => 'mojang/api/usernames-by-uuid',
    'POST /mojang/profiles' => 'mojang/api/uuids-by-usernames',
    'GET /mojang/services/minecraft/profile' => 'mojang/services/profile',

    // authlib-injector
    '/authlib-injector/authserver/<action>' => 'authserver/authentication/<action>',
    '/authlib-injector/sessionserver/session/minecraft/join' => 'session/session/join',
    '/authlib-injector/sessionserver/session/minecraft/hasJoined' => 'session/session/has-joined',
    '/authlib-injector/sessionserver/session/minecraft/profile/<uuid>' => 'session/session/profile',
    '/authlib-injector/api/profiles/minecraft' => 'mojang/api/uuids-by-usernames',
];
