<?php
declare(strict_types=1);

namespace common\components\EmailsRenderer;

use common\components\EmailsRenderer\Request\TemplateRequest;
use common\emails\RendererInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class Component extends \yii\base\Component implements RendererInterface {

    /**
     * @var string The address of the templates rendering service.
     */
    public $serviceUrl;

    /**
     * @var string application base domain. Can be omitted for web applications (will be extracted from request)
     */
    public $baseDomain;

    /**
     * @var string base path after the host. For example "/emails-images"
     */
    public $basePath = '';

    /**
     * @var Api
     */
    private $api;

    public function init(): void {
        parent::init();

        if ($this->serviceUrl === null) {
            throw new InvalidConfigException('serviceUrl is required');
        }

        if ($this->baseDomain === null) {
            $this->baseDomain = Yii::$app->urlManager->getHostInfo();
            if ($this->baseDomain === null) {
                throw new InvalidConfigException('Cannot automatically obtain base domain');
            }
        }
    }

    /**
     * @param string $templateName
     * @param string $locale
     * @param array $params
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function render(string $templateName, string $locale, array $params = []): string {
        $request = new TemplateRequest($templateName, $locale, ArrayHelper::merge($params, [
            'assetsHost' => $this->buildBasePath(),
        ]));

        return $this->getApi()->getTemplate($request);
    }

    private function getApi(): Api {
        if ($this->api === null) {
            $this->api = new Api($this->serviceUrl);
        }

        return $this->api;
    }

    private function buildBasePath(): string {
        return FileHelper::normalizePath($this->baseDomain . '/' . $this->basePath, '/');
    }

}
