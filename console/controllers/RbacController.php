<?php
declare(strict_types=1);

namespace console\controllers;

use api\rbac\Permissions as P;
use api\rbac\Roles as R;
use api\rbac\rules\AccountOwner;
use api\rbac\rules\OauthClientOwner;
use Webmozart\Assert\Assert;
use Yii;
use yii\console\Controller;
use yii\rbac\ManagerInterface;
use yii\rbac\Permission;
use yii\rbac\Role;
use yii\rbac\Rule;

class RbacController extends Controller {

    public $defaultAction = 'generate';

    public function actionGenerate(): void {
        $authManager = $this->getAuthManager();
        $authManager->removeAllPermissions();
        $authManager->removeAllRoles();
        $authManager->removeAllRules();

        $permObtainAccountInfo = $this->createPermission(P::OBTAIN_ACCOUNT_INFO);
        $permChangeAccountLanguage = $this->createPermission(P::CHANGE_ACCOUNT_LANGUAGE);
        $permChangeAccountUsername = $this->createPermission(P::CHANGE_ACCOUNT_USERNAME);
        $permChangeAccountPassword = $this->createPermission(P::CHANGE_ACCOUNT_PASSWORD);
        $permChangeAccountEmail = $this->createPermission(P::CHANGE_ACCOUNT_EMAIL);
        $permManageTwoFactorAuth = $this->createPermission(P::MANAGE_TWO_FACTOR_AUTH);
        $permDeleteAccount = $this->createPermission(P::DELETE_ACCOUNT);
        $permRestoreAccount = $this->createPermission(P::RESTORE_ACCOUNT);
        $permBlockAccount = $this->createPermission(P::BLOCK_ACCOUNT);
        $permCreateOauthClients = $this->createPermission(P::CREATE_OAUTH_CLIENTS);
        $permViewOauthClients = $this->createPermission(P::VIEW_OAUTH_CLIENTS);
        $permManageOauthClients = $this->createPermission(P::MANAGE_OAUTH_CLIENTS);
        $permCompleteOauthFlow = $this->createPermission(P::COMPLETE_OAUTH_FLOW, AccountOwner::class);

        $permObtainAccountEmail = $this->createPermission(P::OBTAIN_ACCOUNT_EMAIL);
        $permObtainExtendedAccountInfo = $this->createPermission(P::OBTAIN_EXTENDED_ACCOUNT_INFO);

        $permAcceptNewProjectRules = $this->createPermission(P::ACCEPT_NEW_PROJECT_RULES, AccountOwner::class);
        $permObtainOwnAccountInfo = $this->createPermission(P::OBTAIN_OWN_ACCOUNT_INFO, AccountOwner::class);
        $permObtainOwnExtendedAccountInfo = $this->createPermission(P::OBTAIN_OWN_EXTENDED_ACCOUNT_INFO, AccountOwner::class);
        $permChangeOwnAccountLanguage = $this->createPermission(P::CHANGE_OWN_ACCOUNT_LANGUAGE, AccountOwner::class);
        $permChangeOwnAccountUsername = $this->createPermission(P::CHANGE_OWN_ACCOUNT_USERNAME, AccountOwner::class);
        $permChangeOwnAccountPassword = $this->createPermission(P::CHANGE_OWN_ACCOUNT_PASSWORD, AccountOwner::class);
        $permChangeOwnAccountEmail = $this->createPermission(P::CHANGE_OWN_ACCOUNT_EMAIL, AccountOwner::class);
        $permManageOwnTwoFactorAuth = $this->createPermission(P::MANAGE_OWN_TWO_FACTOR_AUTH, AccountOwner::class);
        $permDeleteOwnAccount = $this->createPermission(P::DELETE_OWN_ACCOUNT, AccountOwner::class);
        $permRestoreOwnAccount = $this->createPermission(P::RESTORE_OWN_ACCOUNT, AccountOwner::class);
        $permMinecraftServerSession = $this->createPermission(P::MINECRAFT_SERVER_SESSION);
        $permViewOwnOauthClients = $this->createPermission(P::VIEW_OWN_OAUTH_CLIENTS, OauthClientOwner::class);
        $permManageOwnOauthClients = $this->createPermission(P::MANAGE_OWN_OAUTH_CLIENTS, OauthClientOwner::class);

        $permEscapeIdentityVerification = $this->createPermission(P::ESCAPE_IDENTITY_VERIFICATION);

        $roleAccountsWebUser = $this->createRole(R::ACCOUNTS_WEB_USER);

        $authManager->addChild($permObtainOwnAccountInfo, $permObtainAccountInfo);
        $authManager->addChild($permObtainOwnExtendedAccountInfo, $permObtainExtendedAccountInfo);
        $authManager->addChild($permChangeOwnAccountLanguage, $permChangeAccountLanguage);
        $authManager->addChild($permChangeOwnAccountUsername, $permChangeAccountUsername);
        $authManager->addChild($permChangeOwnAccountPassword, $permChangeAccountPassword);
        $authManager->addChild($permChangeOwnAccountEmail, $permChangeAccountEmail);
        $authManager->addChild($permManageOwnTwoFactorAuth, $permManageTwoFactorAuth);
        $authManager->addChild($permDeleteOwnAccount, $permDeleteAccount);
        $authManager->addChild($permRestoreOwnAccount, $permRestoreAccount);
        $authManager->addChild($permViewOwnOauthClients, $permViewOauthClients);
        $authManager->addChild($permManageOwnOauthClients, $permManageOauthClients);

        $authManager->addChild($permObtainExtendedAccountInfo, $permObtainAccountInfo);
        $authManager->addChild($permObtainExtendedAccountInfo, $permObtainAccountEmail);

        $authManager->addChild($roleAccountsWebUser, $permAcceptNewProjectRules);
        $authManager->addChild($roleAccountsWebUser, $permObtainOwnExtendedAccountInfo);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountLanguage);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountUsername);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountPassword);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountEmail);
        $authManager->addChild($roleAccountsWebUser, $permManageOwnTwoFactorAuth);
        $authManager->addChild($roleAccountsWebUser, $permDeleteOwnAccount);
        $authManager->addChild($roleAccountsWebUser, $permRestoreOwnAccount);
        $authManager->addChild($roleAccountsWebUser, $permCompleteOauthFlow);
        $authManager->addChild($roleAccountsWebUser, $permCreateOauthClients);
        $authManager->addChild($roleAccountsWebUser, $permViewOwnOauthClients);
        $authManager->addChild($roleAccountsWebUser, $permManageOwnOauthClients);
    }

    private function createRole(string $name): Role {
        $authManager = $this->getAuthManager();
        $role = $authManager->createRole($name);
        Assert::true($authManager->add($role), 'Cannot save role in authManager');

        return $role;
    }

    private function createPermission(string $name, string $ruleClassName = null): Permission {
        $authManager = $this->getAuthManager();
        $permission = $authManager->createPermission($name);
        if ($ruleClassName !== null) {
            $rule = new $ruleClassName();
            Assert::isInstanceOf($rule, Rule::class, 'ruleClassName must be rule class name');

            $ruleFromAuthManager = $authManager->getRule($rule->name);
            if ($ruleFromAuthManager === null) {
                $authManager->add($rule);
            }

            $permission->ruleName = $rule->name;
        }

        Assert::true($authManager->add($permission), 'Cannot save permission in authManager');

        return $permission;
    }

    private function getAuthManager(): ManagerInterface {
        return Yii::$app->authManager;
    }

}
