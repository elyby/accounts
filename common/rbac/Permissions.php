<?php
namespace common\rbac;

final class Permissions {

    // Top level Controller permissions
    public const OBTAIN_ACCOUNT_INFO = 'obtain_account_info';
    public const CHANGE_ACCOUNT_LANGUAGE = 'change_account_language';
    public const CHANGE_ACCOUNT_USERNAME = 'change_account_username';
    public const CHANGE_ACCOUNT_PASSWORD = 'change_account_password';
    public const CHANGE_ACCOUNT_EMAIL = 'change_account_email';
    public const MANAGE_TWO_FACTOR_AUTH = 'manage_two_factor_auth';
    public const BLOCK_ACCOUNT = 'block_account';
    public const COMPLETE_OAUTH_FLOW = 'complete_oauth_flow';

    // Personal level controller permissions
    public const OBTAIN_OWN_ACCOUNT_INFO = 'obtain_own_account_info';
    public const OBTAIN_OWN_EXTENDED_ACCOUNT_INFO = 'obtain_own_extended_account_info';
    public const CHANGE_OWN_ACCOUNT_LANGUAGE = 'change_own_account_language';
    public const ACCEPT_NEW_PROJECT_RULES = 'accept_new_project_rules';
    public const CHANGE_OWN_ACCOUNT_USERNAME = 'change_own_account_username';
    public const CHANGE_OWN_ACCOUNT_PASSWORD = 'change_own_account_password';
    public const CHANGE_OWN_ACCOUNT_EMAIL = 'change_own_account_email';
    public const MANAGE_OWN_TWO_FACTOR_AUTH = 'manage_own_two_factor_auth';
    public const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';

    // Data permissions
    public const OBTAIN_ACCOUNT_EMAIL = 'obtain_account_email';
    public const OBTAIN_EXTENDED_ACCOUNT_INFO = 'obtain_account_extended_info';

}
