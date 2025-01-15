<?php
declare(strict_types=1);

namespace common\components\OAuth2\Entities;

use common\models\OauthClient;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class ClientEntity implements ClientEntityInterface {
    use EntityTrait;
    use ClientTrait;

    /**
     * @phpstan-param non-empty-string $id
     * @phpstan-param string|list<string> $redirectUri
     */
    public function __construct(
        string $id,
        string $name,
        string|array $redirectUri,
        private readonly bool $isTrusted = false,
        public readonly ?OauthClient $model = null,
    ) {
        $this->identifier = $id;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
    }

    public static function fromModel(OauthClient $model): self {
        return new self(
            id: $model->id, // @phpstan-ignore argument.type
            name: $model->name,
            redirectUri: $model->redirect_uri ?: '',
            isTrusted: (bool)$model->is_trusted,
            model: $model,
        );
    }

    public function isConfidential(): bool {
        return match ($this->model->type) {
            OauthClient::TYPE_WEB_APPLICATION => true,
            OauthClient::TYPE_DESKTOP_APPLICATION => false,
            OauthClient::TYPE_MINECRAFT_SERVER => true,
        };
    }

    public function isTrusted(): bool {
        return $this->isTrusted;
    }

}
