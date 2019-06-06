<?php
declare(strict_types=1);

namespace common\emails;

use common\components\EmailsRenderer\RendererInterface;
use ErrorException;
use Exception;
use yii\mail\MessageInterface;

abstract class TemplateWithRenderer extends Template {

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var string
     */
    private $locale;

    /**
     * @inheritdoc
     */
    public function __construct($to, string $locale, RendererInterface $renderer) {
        parent::__construct($to);
        $this->locale = $locale;
        $this->renderer = $renderer;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getRenderer(): RendererInterface {
        return $this->renderer;
    }

    /**
     * Метод должен возвращать имя шаблона, который должен быть использован.
     * Имена можно взять в репозитории elyby/email-renderer
     *
     * @return string
     */
    abstract public function getTemplateName(): string;

    final protected function getView() {
        return $this->getTemplateName();
    }

    /**
     * @return MessageInterface
     * @throws ErrorException
     */
    protected function createMessage(): MessageInterface {
        return $this->getMailer()
            ->compose()
            ->setHtmlBody($this->render())
            ->setTo($this->getTo())
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());
    }

    /**
     * @return string
     * @throws ErrorException
     */
    private function render(): string {
        try {
            return $this->getRenderer()->render($this->getTemplateName(), $this->getLocale(), $this->getParams());
        } catch (Exception $e) {
            throw new ErrorException('Unable to render the template', 0, 1, __FILE__, __LINE__, $e);
        }
    }

}
