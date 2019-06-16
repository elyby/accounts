<?php
declare(strict_types=1);

namespace common\emails;

use Yii;
use yii\base\InvalidConfigException;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

abstract class Template {

    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    abstract public function getSubject(): string;

    /**
     * @return array|string
     * @throws InvalidConfigException
     */
    public function getFrom() {
        $fromEmail = Yii::$app->params['fromEmail'] ?? '';
        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        return [$fromEmail => 'Ely.by Accounts'];
    }

    public function getParams(): array {
        return [];
    }

    /**
     * @param string|array $to see \yii\mail\MessageInterface::setTo to know the format.
     *
     * @throws \common\emails\exceptions\CannotSendEmailException
     */
    public function send($to): void {
        if (!$this->createMessage($to)->send()) {
            throw new exceptions\CannotSendEmailException();
        }
    }

    /**
     * @return string|array
     */
    abstract protected function getView();

    final protected function getMailer(): MailerInterface {
        return $this->mailer;
    }

    protected function createMessage($for): MessageInterface {
        return $this->getMailer()
            ->compose($this->getView(), $this->getParams())
            ->setTo($for)
            ->setFrom($this->getFrom())
            ->setSubject($this->getSubject());
    }

}
