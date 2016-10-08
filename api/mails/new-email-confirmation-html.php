<?php
/**
 * @var \common\models\Account $account
 * @var string $key
 */
?>

<p>
    This E-mail was specified as new for account <?= $account->username ?>. To confirm this E-mail, pass code
    below into form on site.
</p>
<p>Code: <?= $key ?></p>

<br />

<p><i>// P.S. yes, this is E-mail is not designed yet :)</i></p>
