<?php
return [
    '/accounts/change-email/initialize' => 'accounts/change-email-initialize',
    '/accounts/change-email/submit-new-email' => 'accounts/change-email-submit-new-email',
    '/accounts/change-email/confirm-new-email' => 'accounts/change-email-confirm-new-email',

    '/oauth2/v1/<action>' => 'oauth/<action>',

    '/account/v1/info' => 'identity-info/index',

    '/minecraft/session/join' => 'session/session/join',
    '/minecraft/session/legacy/join' => 'session/session/join-legacy',
    '/minecraft/session/hasJoined' => 'session/session/has-joined',
    '/minecraft/session/legacy/hasJoined' => 'session/session/has-joined-legacy',
];
