<?php
namespace common\helpers;

final class Error {

    public const string USERNAME_REQUIRED = 'error.username_required';
    public const string USERNAME_TOO_SHORT = 'error.username_too_short';
    public const string USERNAME_TOO_LONG = 'error.username_too_long';
    public const string USERNAME_INVALID = 'error.username_invalid';
    public const string USERNAME_NOT_AVAILABLE = 'error.username_not_available';

    public const string EMAIL_REQUIRED = 'error.email_required';
    public const string EMAIL_TOO_LONG = 'error.email_too_long';
    public const string EMAIL_INVALID = 'error.email_invalid';
    public const string EMAIL_IS_TEMPMAIL = 'error.email_is_tempmail';
    public const string EMAIL_HOST_IS_NOT_ALLOWED = 'error.email_host_is_not_allowed';
    public const string EMAIL_NOT_AVAILABLE = 'error.email_not_available';
    public const string EMAIL_NOT_FOUND = 'error.email_not_found';

    public const string LOGIN_REQUIRED = 'error.login_required';
    public const string LOGIN_NOT_EXIST = 'error.login_not_exist';

    public const string PASSWORD_REQUIRED = 'error.password_required';
    public const string PASSWORD_INCORRECT = 'error.password_incorrect';
    public const string PASSWORD_TOO_SHORT = 'error.password_too_short';

    public const string KEY_REQUIRED = 'error.key_required';
    public const string KEY_NOT_EXISTS = 'error.key_not_exists';
    public const string KEY_EXPIRE = 'error.key_expire';

    public const string ACCOUNT_BANNED = 'error.account_banned';
    public const string ACCOUNT_NOT_ACTIVATED = 'error.account_not_activated';
    public const string ACCOUNT_ALREADY_ACTIVATED = 'error.account_already_activated';
    public const string ACCOUNT_CANNOT_RESEND_MESSAGE = 'error.account_cannot_resend_message';

    public const string RECENTLY_SENT_MESSAGE = 'error.recently_sent_message';

    public const string NEW_PASSWORD_REQUIRED = 'error.newPassword_required';
    public const string NEW_RE_PASSWORD_REQUIRED = 'error.newRePassword_required';
    public const string NEW_RE_PASSWORD_DOES_NOT_MATCH = self::RE_PASSWORD_DOES_NOT_MATCH;

    public const string REFRESH_TOKEN_REQUIRED = 'error.refresh_token_required';
    public const string REFRESH_TOKEN_NOT_EXISTS = 'error.refresh_token_not_exist';

    public const string CAPTCHA_REQUIRED = 'error.captcha_required';
    public const string CAPTCHA_INVALID = 'error.captcha_invalid';

    public const string RULES_AGREEMENT_REQUIRED = 'error.rulesAgreement_required';

    public const string RE_PASSWORD_REQUIRED = 'error.rePassword_required';
    public const string RE_PASSWORD_DOES_NOT_MATCH = 'error.rePassword_does_not_match';

    public const string UNSUPPORTED_LANGUAGE = 'error.unsupported_language';

    public const string SUBJECT_REQUIRED = 'error.subject_required';
    public const string MESSAGE_REQUIRED = 'error.message_required';

    public const string TOTP_REQUIRED = 'error.totp_required';
    public const string TOTP_INCORRECT = 'error.totp_incorrect';

    public const string OTP_ALREADY_ENABLED = 'error.otp_already_enabled';
    public const string OTP_NOT_ENABLED = 'error.otp_not_enabled';

    public const string NAME_REQUIRED = 'error.name_required';

    public const string REDIRECT_URI_REQUIRED = 'error.redirectUri_required';
    public const string REDIRECT_URI_INVALID = 'error.redirectUri_invalid';

    public const string WEBSITE_URL_INVALID = 'error.websiteUrl_invalid';

    public const string MINECRAFT_SERVER_IP_INVALID = 'error.minecraftServerIp_invalid';

}
