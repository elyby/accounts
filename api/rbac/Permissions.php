<?php
declare(strict_types=1);

namespace api\rbac;

final class Permissions {

    // Top level Controller permissions
    public const OBTAIN_ACCOUNT_INFO = 'obtain_account_info';
    public const CHANGE_ACCOUNT_LANGUAGE = 'change_account_language';
    public const CHANGE_ACCOUNT_USERNAME = 'change_account_username';
    public const CHANGE_ACCOUNT_PASSWORD = 'change_account_password';
    public const CHANGE_ACCOUNT_EMAIL = 'change_account_email';
    public const MANAGE_TWO_FACTOR_AUTH = 'manage_two_factor_auth';
    public const DELETE_ACCOUNT = 'delete_account';
    public const RESTORE_ACCOUNT = 'restore_account';
    public const BLOCK_ACCOUNT = 'block_account';
    public const COMPLETE_OAUTH_FLOW = 'complete_oauth_flow';
    public const MANAGE_OAUTH_SESSIONS = 'manage_oauth_sessions';
    public const CREATE_OAUTH_CLIENTS = 'create_oauth_clients';
    public const VIEW_OAUTH_CLIENTS = 'view_oauth_clients';
    public const MANAGE_OAUTH_CLIENTS = 'manage_oauth_clients';

    // Personal level controller permissions
    public const OBTAIN_OWN_ACCOUNT_INFO = 'obtain_own_account_info';
    public const OBTAIN_OWN_EXTENDED_ACCOUNT_INFO = 'obtain_own_extended_account_info';
    public const CHANGE_OWN_ACCOUNT_LANGUAGE = 'change_own_account_language';
    public const ACCEPT_NEW_PROJECT_RULES = 'accept_new_project_rules';
    public const CHANGE_OWN_ACCOUNT_USERNAME = 'change_own_account_username';
    public const CHANGE_OWN_ACCOUNT_PASSWORD = 'change_own_account_password';
    public const CHANGE_OWN_ACCOUNT_EMAIL = 'change_own_account_email';
    public const MANAGE_OWN_TWO_FACTOR_AUTH = 'manage_own_two_factor_auth';
    public const DELETE_OWN_ACCOUNT = 'delete_own_account';
    public const RESTORE_OWN_ACCOUNT = 'restore_own_account';
    public const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';
    public const MANAGE_OWN_OAUTH_SESSIONS = 'manage_own_oauth_sessions';
    public const VIEW_OWN_OAUTH_CLIENTS = 'view_own_oauth_clients';
    public const MANAGE_OWN_OAUTH_CLIENTS = 'manage_own_oauth_clients';

    // Data permissions
    public const OBTAIN_ACCOUNT_EMAIL = 'obtain_account_email';
    public const OBTAIN_EXTENDED_ACCOUNT_INFO = 'obtain_account_extended_info';

    // Service permissions
    public const ESCAPE_IDENTITY_VERIFICATION = 'escape_identity_verification';

}
