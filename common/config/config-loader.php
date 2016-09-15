<?php
use yii\helpers\ArrayHelper;

$rootPath = __DIR__ . '/../..';

$toMerge = [
    require __DIR__ . '/main.php',
];

// Общие окружение-зависимые настройки
$path = __DIR__ . '/common-' . YII_ENV . '.php';
if (file_exists($path)) {
    $toMerge[] = require $path;
}

// Общие локальные настройки
$path = __DIR__ . '/common-local.php';
if (file_exists($path)) {
    $toMerge[] = require $path;
}

// Настройки конкретного приложения
$path = $rootPath . '/' . YII_APPLICATION_MODULE . '/config/main.php';
if (file_exists($path)) {
    $toMerge[] = require $path;
}

// Настройки конкретного приложения для действующего окружения
$path = $rootPath . '/' . YII_APPLICATION_MODULE . '/config/main-' . YII_ENV . '.php';
if (file_exists($path)) {
    $toMerge[] = require $path;
}

// Локальные настройки конкретного приложения
$path = $rootPath . '/' . YII_APPLICATION_MODULE . '/config/main-local.php';
if (file_exists($path)) {
    $toMerge[] = require $path;
}

// не оставляем глобальных переменных, ну кроме $toMerge, хех
unset($path, $rootPath);

return ArrayHelper::merge(...$toMerge);
