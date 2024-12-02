<?php
declare(strict_types=1);

namespace common\emails;

use Yii;
use yii\base\InvalidConfigException;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

abstract class Template {

    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    abstract public function getSubject(): string;

    /**
     * @return array|string
     * @throws InvalidConfigException
     */
    public function getFrom(): array|string {
        $fromEmail = Yii::$app->params['fromEmail'] ?? '';
        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        return [$fromEmail => 'Ely.by Accounts'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array {
        return [];
    }

    /**
     * @param array|string $to see \yii\mail\MessageInterface::setTo to know the format.
     *
     * @throws \common\emails\exceptions\CannotSendEmailException
     */
    public function send(array|string $to): void {
        if (!$this->createMessage($to)->send()) {
            throw new exceptions\CannotSendEmailException();
        }
    }

    /**
     * @return string|array{
     *     html?: string,
     *     text?: string,
     * }
     */
    abstract protected function getView(): string|array;

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
