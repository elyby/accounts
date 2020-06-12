<?php
declare(strict_types=1);

namespace console\controllers;

use common\models\WebHook;
use console\models\WebHookForm;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class WebhooksController extends Controller {

    public function actionCreate(): int {
        $form = new WebHookForm(new WebHook());

        $url = Console::prompt('Enter webhook url:', [
            'required' => true,
            'validator' => function(string $input, ?string &$error) use ($form): bool {
                $form->url = $input;
                if (!$form->validate('url')) {
                    $error = $form->getFirstError('url');
                    return false;
                }

                return true;
            },
        ]);
        $secret = Console::prompt('Enter webhook secret (empty to no secret):');

        $options = $form::getEvents();
        $options[''] = 'Finish input'; // It's needed to allow finish input cycle
        $events = [];

        do {
            $availableOptions = array_diff($options, $events);
            $eventIndex = Console::select('Choose wanted events (submit no input to finish):', $availableOptions);
            if ($eventIndex !== '') {
                $events[] = $options[$eventIndex];
            }
        } while ($eventIndex !== '' || empty($events));

        $form->url = $url;
        $form->events = $events;
        if ($secret !== '') {
            $form->secret = $secret;
        }

        if (!$form->save()) {
            Console::error('Unable to create new webhook. Check errors list below' . PHP_EOL . Console::errorSummary($form));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    // TODO: add action to modify the webhook events

}
