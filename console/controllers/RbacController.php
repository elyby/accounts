<?php
namespace console\controllers;

use common\rbac\Permissions as P;
use common\rbac\Roles as R;
use common\rbac\rules\AccountOwner;
use InvalidArgumentException;
use Yii;
use yii\base\ErrorException;
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
        $permBlockAccount = $this->createPermission(P::BLOCK_ACCOUNT);
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
        $permMinecraftServerSession = $this->createPermission(P::MINECRAFT_SERVER_SESSION);

        $permEscapeIdentityVerification = $this->createPermission(P::ESCAPE_IDENTITY_VERIFICATION);

        $roleAccountsWebUser = $this->createRole(R::ACCOUNTS_WEB_USER);

        $authManager->addChild($permObtainOwnAccountInfo, $permObtainAccountInfo);
        $authManager->addChild($permObtainOwnExtendedAccountInfo, $permObtainExtendedAccountInfo);
        $authManager->addChild($permChangeOwnAccountLanguage, $permChangeAccountLanguage);
        $authManager->addChild($permChangeOwnAccountUsername, $permChangeAccountUsername);
        $authManager->addChild($permChangeOwnAccountPassword, $permChangeAccountPassword);
        $authManager->addChild($permChangeOwnAccountEmail, $permChangeAccountEmail);
        $authManager->addChild($permManageOwnTwoFactorAuth, $permManageTwoFactorAuth);

        $authManager->addChild($permObtainExtendedAccountInfo, $permObtainAccountInfo);
        $authManager->addChild($permObtainExtendedAccountInfo, $permObtainAccountEmail);

        $authManager->addChild($roleAccountsWebUser, $permAcceptNewProjectRules);
        $authManager->addChild($roleAccountsWebUser, $permObtainOwnExtendedAccountInfo);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountLanguage);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountUsername);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountPassword);
        $authManager->addChild($roleAccountsWebUser, $permChangeOwnAccountEmail);
        $authManager->addChild($roleAccountsWebUser, $permManageOwnTwoFactorAuth);
        $authManager->addChild($roleAccountsWebUser, $permCompleteOauthFlow);
    }

    private function createRole(string $name): Role {
        $authManager = $this->getAuthManager();
        $role = $authManager->createRole($name);
        if (!$authManager->add($role)) {
            throw new ErrorException('Cannot save role in authManager');
        }

        return $role;
    }

    private function createPermission(string $name, string $ruleClassName = null): Permission {
        $authManager = $this->getAuthManager();
        $permission = $authManager->createPermission($name);
        if ($ruleClassName !== null) {
            $rule = new $ruleClassName;
            if (!$rule instanceof Rule) {
                throw new InvalidArgumentException('ruleClassName must be rule class name');
            }

            $ruleFromAuthManager = $authManager->getRule($rule->name);
            if ($ruleFromAuthManager === null) {
                $authManager->add($rule);
            }

            $permission->ruleName = $rule->name;
        }

        if (!$authManager->add($permission)) {
            throw new ErrorException('Cannot save permission in authManager');
        }

        return $permission;
    }

    private function getAuthManager(): ManagerInterface {
        return Yii::$app->authManager;
    }

}
