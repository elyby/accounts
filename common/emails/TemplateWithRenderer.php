<?php
declare(strict_types=1);

namespace common\emails;

use Exception;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

abstract class TemplateWithRenderer extends Template {

    /**
     * @var string
     */
    private string $locale = 'en';

    public function __construct(
        MailerInterface $mailer,
        private readonly RendererInterface $renderer,
    ) {
        parent::__construct($mailer);
    }

    public function setLocale(string $locale): void {
        $this->locale = $locale;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    /**
     * This method should return the template's name, which will be rendered.
     * List of available templates names can be found at https://github.com/elyby/emails-renderer
     *
     * @return string
     */
    abstract public function getTemplateName(): string;

    final protected function getRenderer(): RendererInterface {
        return $this->renderer;
    }

    final protected function getView(): string {
        return $this->getTemplateName();
    }

    /**
     * @param string|array $for
     *
     * @return MessageInterface
     * @throws \common\emails\exceptions\CannotRenderEmailException
     */
    protected function createMessage($for): MessageInterface {
        return $this->getMailer()
            ->compose()
            ->setHtmlBody($this->render())
            ->setTo($for)
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());
    }

    /**
     * @return string
     * @throws \common\emails\exceptions\CannotRenderEmailException
     */
    private function render(): string {
        try {
            return $this->getRenderer()->render($this->getTemplateName(), $this->getLocale(), $this->getParams());
        } catch (Exception $e) {
            throw new exceptions\CannotRenderEmailException($e);
        }
    }

}
