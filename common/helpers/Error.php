<?php
namespace common\helpers;

final class Error {

    public const USERNAME_REQUIRED = 'error.username_required';
    public const USERNAME_TOO_SHORT = 'error.username_too_short';
    public const USERNAME_TOO_LONG = 'error.username_too_long';
    public const USERNAME_INVALID = 'error.username_invalid';
    public const USERNAME_NOT_AVAILABLE = 'error.username_not_available';

    public const EMAIL_REQUIRED = 'error.email_required';
    public const EMAIL_TOO_LONG = 'error.email_too_long';
    public const EMAIL_INVALID = 'error.email_invalid';
    public const EMAIL_IS_TEMPMAIL = 'error.email_is_tempmail';
    public const EMAIL_NOT_AVAILABLE = 'error.email_not_available';
    public const EMAIL_NOT_FOUND = 'error.email_not_found';

    public const LOGIN_REQUIRED = 'error.login_required';
    public const LOGIN_NOT_EXIST = 'error.login_not_exist';

    public const PASSWORD_REQUIRED = 'error.password_required';
    public const PASSWORD_INCORRECT = 'error.password_incorrect';
    public const PASSWORD_TOO_SHORT = 'error.password_too_short';

    public const KEY_REQUIRED = 'error.key_required';
    public const KEY_NOT_EXISTS = 'error.key_not_exists';
    public const KEY_EXPIRE = 'error.key_expire';

    public const ACCOUNT_BANNED = 'error.account_banned';
    public const ACCOUNT_NOT_ACTIVATED = 'error.account_not_activated';
    public const ACCOUNT_ALREADY_ACTIVATED = 'error.account_already_activated';
    public const ACCOUNT_CANNOT_RESEND_MESSAGE = 'error.account_cannot_resend_message';

    public const RECENTLY_SENT_MESSAGE = 'error.recently_sent_message';

    public const NEW_PASSWORD_REQUIRED = 'error.newPassword_required';
    public const NEW_RE_PASSWORD_REQUIRED = 'error.newRePassword_required';
    public const NEW_RE_PASSWORD_DOES_NOT_MATCH = self::RE_PASSWORD_DOES_NOT_MATCH;

    public const REFRESH_TOKEN_REQUIRED = 'error.refresh_token_required';
    public const REFRESH_TOKEN_NOT_EXISTS = 'error.refresh_token_not_exist';

    public const CAPTCHA_REQUIRED = 'error.captcha_required';
    public const CAPTCHA_INVALID = 'error.captcha_invalid';

    public const RULES_AGREEMENT_REQUIRED = 'error.rulesAgreement_required';

    public const RE_PASSWORD_REQUIRED = 'error.rePassword_required';
    public const RE_PASSWORD_DOES_NOT_MATCH = 'error.rePassword_does_not_match';

    public const UNSUPPORTED_LANGUAGE = 'error.unsupported_language';

    public const SUBJECT_REQUIRED = 'error.subject_required';
    public const MESSAGE_REQUIRED = 'error.message_required';

    public const TOTP_REQUIRED = 'error.totp_required';
    public const TOTP_INCORRECT = 'error.totp_incorrect';

    public const OTP_ALREADY_ENABLED = 'error.otp_already_enabled';
    public const OTP_NOT_ENABLED = 'error.otp_not_enabled';

    public const NAME_REQUIRED = 'error.name_required';

    public const REDIRECT_URI_REQUIRED = 'error.redirectUri_required';
    public const REDIRECT_URI_INVALID = 'error.redirectUri_invalid';

    public const WEBSITE_URL_INVALID = 'error.websiteUrl_invalid';

    public const MINECRAFT_SERVER_IP_INVALID = 'error.minecraftServerIp_invalid';

}
