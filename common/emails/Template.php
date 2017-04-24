<?php
namespace common\emails;

use common\emails\exceptions\CannotSendEmailException;
use Yii;
use yii\base\InvalidConfigException;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

abstract class Template {

    /**
     * @var \yii\swiftmailer\Mailer
     */
    private $mailer;

    /**
     * @var string|array
     */
    private $to;

    /**
     * @param string|array $to получатель письма. Задаётся как Email или как массив [email => name]
     */
    public function __construct($to) {
        $this->mailer = Yii::$app->mailer;
        $this->to = $to;
    }

    /**
     * @return array|string
     */
    public function getTo() {
        return $this->to;
    }

    abstract public function getSubject(): string;

    /**
     * @return array|string
     * @throws InvalidConfigException
     */
    public function getFrom() {
        $fromEmail = Yii::$app->params['fromEmail'];
        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        return [$fromEmail => 'Ely.by Accounts'];
    }

    public function getParams(): array {
        return [];
    }

    public function getMailer(): MailerInterface {
        return $this->mailer;
    }

    public function send(): void {
        if (!$this->createMessage()->send()) {
            throw new CannotSendEmailException('Unable send email.');
        }
    }

    /**
     * @return string|array
     */
    abstract protected function getView();

    protected function createMessage(): MessageInterface {
        return $this->getMailer()
            ->compose($this->getView(), $this->getParams())
            ->setTo($this->getTo())
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());
    }

}
