<?php
namespace api\emails;

use common\components\EmailRenderer;
use Yii;
use yii\mail\MessageInterface;

abstract class TemplateWithRenderer extends Template {

    /**
     * @var EmailRenderer
     */
    private $emailRenderer;

    /**
     * @var string
     */
    private $locale;

    /**
     * @inheritdoc
     */
    public function __construct($to, string $locale) {
        parent::__construct($to);
        $this->emailRenderer = Yii::$app->emailRenderer;
        $this->locale = $locale;
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function getEmailRenderer(): EmailRenderer {
        return $this->emailRenderer;
    }

    /**
     * Метод должен возвращать имя шаблона, который должен быть использован.
     * Имена можно взять в репозитории elyby/email-renderer
     *
     * @return string
     */
    abstract protected function getTemplateName(): string;

    protected final function getView() {
        return $this->getTemplateName();
    }

    protected function createMessage(): MessageInterface {
        return $this->getMailer()
            ->compose()
            ->setHtmlBody($this->render())
            ->setTo($this->getTo())
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());
    }

    private function render(): string {
        return $this->getEmailRenderer()
            ->getTemplate($this->getTemplateName())
            ->setLocale($this->getLocale())
            ->setParams($this->getParams())
            ->render();
    }

}
