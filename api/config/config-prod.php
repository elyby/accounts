<?php
declare(strict_types=1);

return [
    'components' => [
        'request' => [
            // Only the client's IP and protocol are trusted from proxies.
            // The host is always taken from the Host header.
            'trustedHosts' => [
                'private' => ['X-Forwarded-For', 'X-Forwarded-Proto'],
            ],
        ],
    ],
];
