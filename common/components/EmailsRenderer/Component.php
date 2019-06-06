<?php
declare(strict_types=1);

namespace common\components\EmailsRenderer;

use common\components\EmailsRenderer\Request\TemplateRequest;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * @property string $baseDomain
 */
class Component extends \yii\base\Component implements RendererInterface {

    /**
     * @var string The address of the templates rendering service.
     */
    public $serviceUrl;

    /**
     * @var string базовый путь после хоста. Должен начинаться слешем и заканчиваться без него.
     * Например "/email-images"
     */
    public $basePath = '';

    /**
     * @var Api
     */
    private $api;

    /**
     * @var string
     */
    private $_baseDomain;

    public function init(): void {
        parent::init();

        if ($this->serviceUrl === null) {
            throw new InvalidConfigException('serviceUrl is required');
        }

        if ($this->_baseDomain === null) {
            $this->_baseDomain = Yii::$app->urlManager->getHostInfo();
            if ($this->_baseDomain === null) {
                throw new InvalidConfigException('Cannot automatically obtain base domain');
            }
        }
    }

    public function setBaseDomain(string $baseDomain): void {
        $this->_baseDomain = $baseDomain;
    }

    public function getBaseDomain(): string {
        return $this->_baseDomain;
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
        return FileHelper::normalizePath($this->_baseDomain . '/' . $this->basePath, '/');
    }

}
