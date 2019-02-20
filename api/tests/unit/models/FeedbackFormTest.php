<?php
namespace codeception\api\unit\models;

use api\models\FeedbackForm;
use common\models\Account;
use api\tests\unit\TestCase;
use yii\swiftmailer\Message;

class FeedbackFormTest extends TestCase {

    public function testSendMessage() {
        $model = new FeedbackForm([
            'subject' => 'Тема обращения',
            'email' => 'erickskrauch@ely.by',
            'message' => 'Привет мир!',
        ]);
        $this->assertTrue($model->sendMessage());
        $this->tester->seeEmailIsSent(1, 'message file exists');
    }

    public function testSendMessageWithEmail() {
        /** @var FeedbackForm|\PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->getMockBuilder(FeedbackForm::class)
            ->setMethods(['getAccount'])
            ->setConstructorArgs([[
                'subject' => 'Тема обращения',
                'email' => 'erickskrauch@ely.by',
                'message' => 'Привет мир!',
            ]])
            ->getMock();

        $model
            ->expects($this->any())
            ->method('getAccount')
            ->will($this->returnValue(new Account([
                'id' => '123',
                'username' => 'Erick',
                'email' => 'find-this@email.net',
                'created_at' => time() - 86400,
            ])));
        $this->assertTrue($model->sendMessage());
        /** @var Message $message */
        $message = $this->tester->grabLastSentEmail();
        $this->assertInstanceOf(Message::class, $message);
        $data = (string)$message;
        $this->assertContains('find-this@email.net', $data);
    }

}
