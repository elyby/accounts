<?php
/**
 * @var \yii\base\View $this
 * @var \api\models\FeedbackForm $model
 * @var \common\models\Account|null $account
 */

use yii\helpers\Html;

?>

<h2>Форма обратной связи Account Ely.by</h2>
<hr />
<br />
<br />
Письмо отправлено: <?= date('d.m.Y, в H:i') ?><br />
E-mail отправителя: <?= $model->email ?><br />
<?php if ($account) { ?>
    Ник отправителя: <?= $account->username ?><br />
    <?php if ($account->email !== $model->email) { ?>
        Регистрационный E-mail: <?= $account->email ?><br />
    <?php } ?>
    <?php $link = 'http://ely.by/u' . $account->id; ?>
    Ссылка на профиль: <?= Html::a($link, $link) ?><br />
    Дата регистрации: <?= date('d.m.Y, в H:i', $account->created_at) ?><br />
<?php } ?>
Тема: <?= Yii::$app->formatter->asText($model->subject) ?><br />
Текст:<br />
<?= Yii::$app->formatter->asNtext($model->message) ?>
