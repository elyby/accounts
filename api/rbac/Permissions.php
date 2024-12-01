<?php
declare(strict_types=1);

namespace api\rbac;

final class Permissions {

    // Top level Controller permissions
    public const string OBTAIN_ACCOUNT_INFO = 'obtain_account_info';
    public const string CHANGE_ACCOUNT_LANGUAGE = 'change_account_language';
    public const string CHANGE_ACCOUNT_USERNAME = 'change_account_username';
    public const string CHANGE_ACCOUNT_PASSWORD = 'change_account_password';
    public const string CHANGE_ACCOUNT_EMAIL = 'change_account_email';
    public const string MANAGE_TWO_FACTOR_AUTH = 'manage_two_factor_auth';
    public const string DELETE_ACCOUNT = 'delete_account';
    public const string RESTORE_ACCOUNT = 'restore_account';
    public const string BLOCK_ACCOUNT = 'block_account';
    public const string COMPLETE_OAUTH_FLOW = 'complete_oauth_flow';
    public const string MANAGE_OAUTH_SESSIONS = 'manage_oauth_sessions';
    public const string CREATE_OAUTH_CLIENTS = 'create_oauth_clients';
    public const string VIEW_OAUTH_CLIENTS = 'view_oauth_clients';
    public const string MANAGE_OAUTH_CLIENTS = 'manage_oauth_clients';

    // Personal level controller permissions
    public const string OBTAIN_OWN_ACCOUNT_INFO = 'obtain_own_account_info';
    public const string OBTAIN_OWN_EXTENDED_ACCOUNT_INFO = 'obtain_own_extended_account_info';
    public const string CHANGE_OWN_ACCOUNT_LANGUAGE = 'change_own_account_language';
    public const string ACCEPT_NEW_PROJECT_RULES = 'accept_new_project_rules';
    public const string CHANGE_OWN_ACCOUNT_USERNAME = 'change_own_account_username';
    public const string CHANGE_OWN_ACCOUNT_PASSWORD = 'change_own_account_password';
    public const string CHANGE_OWN_ACCOUNT_EMAIL = 'change_own_account_email';
    public const string MANAGE_OWN_TWO_FACTOR_AUTH = 'manage_own_two_factor_auth';
    public const string DELETE_OWN_ACCOUNT = 'delete_own_account';
    public const string RESTORE_OWN_ACCOUNT = 'restore_own_account';
    public const string MINECRAFT_SERVER_SESSION = 'minecraft_server_session';
    public const string MANAGE_OWN_OAUTH_SESSIONS = 'manage_own_oauth_sessions';
    public const string VIEW_OWN_OAUTH_CLIENTS = 'view_own_oauth_clients';
    public const string MANAGE_OWN_OAUTH_CLIENTS = 'manage_own_oauth_clients';

    // Data permissions
    public const string OBTAIN_ACCOUNT_EMAIL = 'obtain_account_email';
    public const string OBTAIN_EXTENDED_ACCOUNT_INFO = 'obtain_account_extended_info';

    // Service permissions
    public const string ESCAPE_IDENTITY_VERIFICATION = 'escape_identity_verification';

}
