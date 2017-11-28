<?php
namespace common\components;

use Ely\Email\Renderer;
use Ely\Email\TemplateBuilder;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class EmailRenderer extends Component {

    /**
     * @var string базовый путь после хоста. Должен начинаться слешем и заканчиваться без него.
     * Например "/email-images"
     */
    public $basePath = '';

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $_baseDomain;

    public function __construct(array $config = []) {
        parent::__construct($config);

        if ($this->_baseDomain === null) {
            $this->_baseDomain = Yii::$app->urlManager->getHostInfo();
            if ($this->_baseDomain === null) {
                throw new InvalidConfigException('Cannot automatically obtain base domain');
            }
        }

        $this->renderer = new Renderer($this->buildBasePath());
    }

    public function setBaseDomain(string $baseDomain) {
        $this->_baseDomain = $baseDomain;
        $this->renderer->setBaseDomain($this->buildBasePath());
    }

    public function getBaseDomain() : string {
        return $this->_baseDomain;
    }

    /**
     * @param string $templateName
     * @return TemplateBuilder
     */
    public function getTemplate(string $templateName): TemplateBuilder {
        return $this->renderer->getTemplate($templateName);
    }

    /**
     * @param TemplateBuilder $template
     * @throws \Ely\Email\RendererException
     * @return string
     */
    public function render(TemplateBuilder $template): string {
        return $this->renderer->render($template);
    }

    private function buildBasePath(): string {
        return $this->_baseDomain . $this->basePath;
    }

}
