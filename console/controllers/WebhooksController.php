<?php
declare(strict_types=1);

namespace console\controllers;

use common\models\WebHook;
use console\models\WebHookForm;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\helpers\Console as C;

class WebhooksController extends Controller {

    public $defaultAction = 'list';

    public function actionList(): void {
        $rows = [];
        /** @var WebHook $webHook */
        foreach (WebHook::find()->with('events')->all() as $webHook) {
            $rows[] = [$webHook->id, $webHook->url, $webHook->secret, implode(', ', $webHook->events)];
        }

        echo (new Table([
            'headers' => ['id', 'url', 'secret', 'events'],
            'rows' => $rows,
        ]))->run();
    }

    public function actionCreate(): int {
        return $this->runForm(new WebHookForm(new WebHook()));
    }

    public function actionUpdate(int $id): int {
        /** @var WebHook|null $webHook */
        $webHook = WebHook::findOne(['id' => $id]);
        if ($webHook === null) {
            C::error("Entity with id {$id} isn't found.");

            return ExitCode::DATAERR;
        }

        return $this->runForm(new WebHookForm($webHook));
    }

    private function runForm(WebHookForm $form): int {
        C::prompt(C::ansiFormat('Enter webhook url:', [C::FG_GREY]), [
            'required' => true,
            'default' => $form->url,
            'validator' => function(string $input, ?string &$error) use ($form): bool {
                $form->url = $input;
                if (!$form->validate('url')) {
                    $error = $form->getFirstError('url');
                    return false;
                }

                return true;
            },
        ]);

        $secret = C::prompt(C::ansiFormat('Enter webhook secret (empty to no secret):', [C::FG_GREY]), [
            'default' => $form->secret,
        ]);
        if ($secret !== '') {
            $form->secret = $secret;
        }

        $allEvents = WebHookForm::getEvents();
        do {
            $options = [];
            foreach ($allEvents as $id => $option) {
                if (in_array($option, $form->events, true)) {
                    $options["-{$id}"] = $option; // Cast to string to create "-0" index
                } else {
                    $options[$id] = $option;
                }
            }

            $options[''] = 'Finish input'; // This needed to allow finish input cycle

            $eventIndex = C::select(
                C::ansiFormat('Choose wanted events (submit no input to finish):', [C::FG_GREY]),
                $options,
            );
            if ($eventIndex === '') {
                continue;
            }

            if ($eventIndex[0] === '-') {
                unset($form->events[array_search($options[$eventIndex], $form->events, true)]);
            } else {
                $form->events[] = $options[$eventIndex];
            }
        } while ($eventIndex !== '' || empty($form->events));

        if (!$form->save()) {
            C::error('Unable to create new webhook. Check errors list below' . PHP_EOL . C::errorSummary($form));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

}
