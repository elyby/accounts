<?php
/**
 * @var array $params
 */
return [
    '/oauth2/v1/<action>' => 'oauth/<action>',

    'GET /v1/accounts/<id:\d+>' => 'accounts/default/get',
    'GET /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/get-two-factor-auth-credentials',
    'POST /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/enable-two-factor-auth',
    'DELETE /v1/accounts/<id:\d+>/two-factor-auth' => 'accounts/default/disable-two-factor-auth',
    'POST /v1/accounts/<id:\d+>/ban' => 'accounts/default/ban',
    'DELETE /v1/accounts/<id:\d+>/ban' => 'accounts/default/pardon',
    '/v1/accounts/<id:\d+>/<action>' => 'accounts/default/<action>',

    '/account/v1/info' => 'identity-info/index',

    '/minecraft/session/join' => 'session/session/join',
    '/minecraft/session/legacy/join' => 'session/session/join-legacy',
    '/minecraft/session/hasJoined' => 'session/session/has-joined',
    '/minecraft/session/legacy/hasJoined' => 'session/session/has-joined-legacy',
    '/minecraft/session/profile/<uuid>' => 'session/session/profile',

    '/mojang/profiles/<username>' => 'mojang/api/uuid-by-username',
    '/mojang/profiles/<uuid>/names' => 'mojang/api/usernames-by-uuid',
    'POST /mojang/profiles' => 'mojang/api/uuids-by-usernames',

    "//{$params['authserverHost']}/mojang/api/users/profiles/minecraft/<username>" => 'mojang/api/uuid-by-username',
    "//{$params['authserverHost']}/mojang/api/user/profiles/<uuid>/names" => 'mojang/api/usernames-by-uuid',
    "POST //{$params['authserverHost']}/mojang/api/profiles/minecraft" => 'mojang/api/uuids-by-usernames',
];
