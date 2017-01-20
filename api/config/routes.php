<?php
/**
 * @var array $params
 */
return [
    '/accounts/change-email/initialize' => 'accounts/change-email-initialize',
    '/accounts/change-email/submit-new-email' => 'accounts/change-email-submit-new-email',
    '/accounts/change-email/confirm-new-email' => 'accounts/change-email-confirm-new-email',

    'POST /two-factor-auth' => 'two-factor-auth/activate',
    'DELETE /two-factor-auth' => 'two-factor-auth/disable',

    '/oauth2/v1/<action>' => 'oauth/<action>',

    '/account/v1/info' => 'identity-info/index',

    '/minecraft/session/join' => 'session/session/join',
    '/minecraft/session/legacy/join' => 'session/session/join-legacy',
    '/minecraft/session/hasJoined' => 'session/session/has-joined',
    '/minecraft/session/legacy/hasJoined' => 'session/session/has-joined-legacy',
    '/minecraft/session/profile/<uuid>' => 'session/session/profile',

    '/mojang/profiles/<username>' => 'mojang/api/uuid-by-username',
    '/mojang/profiles/<uuid>/names' => 'mojang/api/usernames-by-uuid',
    'POST /mojang/profiles' => 'mojang/api/uuids-by-usernames',

    "<protocol:http|https>://{$params['authserverHost']}/mojang/api/users/profiles/minecraft/<username>" => 'mojang/api/uuid-by-username',
    "<protocol:http|https>://{$params['authserverHost']}/mojang/api/user/profiles/<uuid>/names" => 'mojang/api/usernames-by-uuid',
    "POST <protocol:http|https>://{$params['authserverHost']}/mojang/api/profiles/minecraft" => 'mojang/api/uuids-by-usernames',
];
