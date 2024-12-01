<?php
declare(strict_types=1);

namespace common\config;

use yii\helpers\ArrayHelper;

final class ConfigLoader {

    private const string ROOT_PATH = __DIR__ . '/../..';

    private string $application;

    public function __construct(string $application) {
        $this->application = $application;
    }

    public function getEnvironment(): string {
        return YII_ENV;
    }

    public function getConfig(): array {
        $toMerge = [
            require __DIR__ . '/config.php',
        ];

        // Common env-dependent configuration
        $path = __DIR__ . '/config-' . YII_ENV . '.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // Common local configuration
        $path = __DIR__ . '/config-local.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // App-related base configuration
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // App-related env-dependent configuration
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config-' . YII_ENV . '.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // App-related local configuration
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config-local.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // @phpstan-ignore arguments.count (Should be covered by Yii2 extension)
        return ArrayHelper::merge(...$toMerge);
    }

    /**
     * @return array<string, mixed>
     */
    public static function load(string $application): array {
        return (new self($application))->getConfig();
    }

}
