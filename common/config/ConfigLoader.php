<?php
declare(strict_types=1);

namespace common\config;

use yii\helpers\ArrayHelper;

class ConfigLoader {

    private const ROOT_PATH = __DIR__ . '/../..';

    private $application;

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

        // Общие окружение-зависимые настройки
        $path = __DIR__ . '/config-' . YII_ENV . '.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // Общие локальные настройки
        $path = __DIR__ . '/config-local.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // Настройки конкретного приложения
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // Настройки конкретного приложения для действующего окружения
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config-' . YII_ENV . '.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        // Локальные настройки конкретного приложения
        $path = self::ROOT_PATH . '/' . $this->application . '/config/config-local.php';
        if (file_exists($path)) {
            $toMerge[] = require $path;
        }

        return ArrayHelper::merge(...$toMerge);
    }

    public static function load(string $application): array {
        return (new static($application))->getConfig();
    }

}
