<?php
/**
 * @var \common\models\Account $account
 * @var string $key
 */
?>

This E-mail was specified as new for account <?= $account->username ?>. To confirm this E-mail, pass code below into form on site.

Code: <?= $key ?>

// P.S. yes, this is E-mail is not designed yet :)
