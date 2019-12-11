<?php

use Carbon\Carbon;

return [
    'ZZQP8sS9urzriy8N9h6FwFNMOH3PkZ5T5PLqS6SX' => [
        'id' => 'ZZQP8sS9urzriy8N9h6FwFNMOH3PkZ5T5PLqS6SX',
        'session_id' => 1,
        'expire_time' => Carbon::now()->addHour()->getTimestamp(),
    ],
    'rc0sOF1SLdOxuD3bJcCQENmGTeYrGgy12qJScMx4' => [
        'id' => 'rc0sOF1SLdOxuD3bJcCQENmGTeYrGgy12qJScMx4',
        'session_id' => 1,
        'expire_time' => Carbon::now()->subHour()->getTimestamp(),
    ],
];
