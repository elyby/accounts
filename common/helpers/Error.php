<?php
namespace common\helpers;

final class Error {

    const USERNAME_REQUIRED = 'error.username_required';
    const USERNAME_TOO_SHORT = 'error.username_too_short';
    const USERNAME_TOO_LONG = 'error.username_too_long';
    const USERNAME_INVALID = 'error.username_invalid';
    const USERNAME_NOT_AVAILABLE = 'error.username_not_available';

    const EMAIL_REQUIRED = 'error.email_required';
    const EMAIL_TOO_LONG = 'error.email_too_long';
    const EMAIL_INVALID = 'error.email_invalid';
    const EMAIL_IS_TEMPMAIL = 'error.email_is_tempmail';
    const EMAIL_NOT_AVAILABLE = 'error.email_not_available';
    const EMAIL_NOT_FOUND = 'error.email_not_found';

    const LOGIN_REQUIRED = 'error.login_required';
    const LOGIN_NOT_EXIST = 'error.login_not_exist';

    const PASSWORD_REQUIRED = 'error.password_required';
    const PASSWORD_INVALID = 'error.password_invalid';
    /**
     * TODO: На фронте с password_incorrect и password_invalid возникла неувязочка.
     * Один возникает у формы входа и там уместно предлагать восстановление пароля.
     * Другой возникает у паролезащищённой формы и там уже ничего предлагать не нужно.
     * Но по факту это ведь одна и та же ошибка.
     * @deprecated
     */
    const PASSWORD_INCORRECT = 'error.password_incorrect';
    const PASSWORD_TOO_SHORT = 'error.password_too_short';

    const KEY_REQUIRED = 'error.key_required';
    const KEY_NOT_EXISTS = 'error.key_not_exists';
    const KEY_EXPIRE = 'error.key_expire';

    const ACCOUNT_NOT_ACTIVATED = 'error.account_not_activated';
    const ACCOUNT_ALREADY_ACTIVATED = 'error.account_already_activated';
    const ACCOUNT_CANNOT_RESEND_MESSAGE = 'error.account_cannot_resend_message';

    const RECENTLY_SENT_MESSAGE = 'error.recently_sent_message';

    const NEW_PASSWORD_REQUIRED = 'error.newPassword_required';
    const NEW_RE_PASSWORD_REQUIRED = 'error.newRePassword_required';
    const NEW_RE_PASSWORD_DOES_NOT_MATCH = self::RE_PASSWORD_DOES_NOT_MATCH;

    const REFRESH_TOKEN_REQUIRED = 'error.refresh_token_required';
    const REFRESH_TOKEN_NOT_EXISTS = 'error.refresh_token_not_exist';

    const CAPTCHA_INVALID = 'error.captcha_invalid';

    const RULES_AGREEMENT_REQUIRED = 'error.rulesAgreement_required';

    const RE_PASSWORD_REQUIRED = 'error.rePassword_required';
    const RE_PASSWORD_DOES_NOT_MATCH = 'error.rePassword_does_not_match';

    const UNSUPPORTED_LANGUAGE = 'error.unsupported_language';

    const SUBJECT_REQUIRED = 'error.subject_required';
    const MESSAGE_REQUIRED = 'error.message_required';

}
