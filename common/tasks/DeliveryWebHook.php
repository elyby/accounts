<?php
declare(strict_types=1);

namespace common\tasks;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Yii;
use yii\queue\RetryableJobInterface;

class DeliveryWebHook implements RetryableJobInterface {

    public string $type;

    public string $url;

    public ?string $secret = null;

    public array $payloads;

    public function getTtr(): int {
        return 65;
    }

    public function canRetry($attempt, $error): bool {
        if ($attempt >= 5) {
            return false;
        }

        if ($error instanceof ServerException || $error instanceof ConnectException) {
            return true;
        }

        return false;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute($queue): void {
        $client = $this->createClient();
        try {
            $client->request('POST', $this->url, [
                'headers' => [
                    'User-Agent' => 'Account-Ely-Hookshot/' . Yii::$app->version,
                    'X-Ely-Accounts-Event' => $this->type,
                ],
                'form_params' => $this->payloads,
            ]);
        } catch (ClientException $e) {
            Yii::info("Delivery for {$this->url} has failed with {$e->getResponse()->getStatusCode()} status.");

            return;
        }
    }

    protected function createClient(): ClientInterface {
        return new GuzzleClient([
            'handler' => $this->createStack(),
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);
    }

    protected function createStack(): HandlerStack {
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function(RequestInterface $request): RequestInterface {
            if (empty($this->secret)) {
                return $request;
            }

            $payload = (string)$request->getBody();
            $signature = hash_hmac('sha1', $payload, $this->secret);

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return $request->withHeader('X-Hub-Signature', 'sha1=' . $signature);
        }));

        return $stack;
    }

}
