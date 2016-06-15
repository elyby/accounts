<?php
namespace codeception\api\unit\models;

use api\models\FeedbackForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\TestCase;
use Yii;

class FeedbackFormTest extends TestCase {
    use Specify;

    const FILE_NAME = 'testing_message.eml';

    public function setUp() {
        parent::setUp();
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->fileTransportCallback = function() {
            return self::FILE_NAME;
        };
    }

    protected function tearDown() {
        if (file_exists($this->getMessageFile())) {
            unlink($this->getMessageFile());
        }

        parent::tearDown();
    }

    public function testSendMessage() {
        $this->specify('send email', function() {
            $model = new FeedbackForm([
                'subject' => 'Тема обращения',
                'email' => 'erickskrauch@ely.by',
                'message' => 'Привет мир!',
            ]);
            expect($model->sendMessage())->true();
            expect_file('message file exists', $this->getMessageFile())->exists();
        });

        $this->specify('send email with user info', function() {
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
            expect($model->sendMessage())->true();
            expect_file('message file exists', $this->getMessageFile())->exists();
            $data = file_get_contents($this->getMessageFile());
            expect(strpos($data, 'find-this@email.net'))->notEquals(false);
        });
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        return Yii::getAlias($mailer->fileTransportPath) . '/' . self::FILE_NAME;
    }

}
