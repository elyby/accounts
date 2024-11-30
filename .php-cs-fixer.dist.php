<?php
declare(strict_types=1);

/**
 * Do not adjust this file for your environment manually.
 * Copy it as .php-cs-fixer.php (without .dist part) and then adjust.
 */

$finder = \PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('data')
    ->exclude('docker')
    ->exclude('frontend')
    ->notPath('common/emails/views')
    ->notPath('common/mail/layouts')
    ->notPath('#.*/runtime#')
    ->notPath('autocompletion.php')
    ->exclude('_output')
    ->exclude('_generated')
    // Remove the line below if your host OS is Windows, because it'll try to fix file, that should be executed
    // on Linux environment
    ->name('yii');

return \Ely\CS\Config::create([
    'self_accessor' => false,
])->setFinder($finder);
