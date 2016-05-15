<?php
/**
 * @var \common\models\Account $account
 * @var string $key
 */
?>

<p>
    Этот E-mail адрес был указан как новый для аккаунта <?= $account->username ?>. Чтобы подтвердить это E-mail,
    введите код ниже в форму на сайте.
</p>
<p>Код: <?= $key ?></p>
