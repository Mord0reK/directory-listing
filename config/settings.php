<?php

$defaultIconsFile = __DIR__ . '/icons.json';
$customIconsFile = __DIR__ . '/../assets/icons/custom/icons.json';

$iconsFile = (is_file($customIconsFile) && is_readable($customIconsFile))
    ? $customIconsFile
    : $defaultIconsFile;

return [
    'base_dir'   => __DIR__ . '/../content',
    'site_name'  => 'Directory Listing',
    'hidden'     => ['.', '..', '.git', '.gitignore', '.htaccess', '.dir.json'],
    'icons_file' => $iconsFile,
    'webhook_token' => $_ENV['WEBHOOK_TOKEN'] ?? $_SERVER['WEBHOOK_TOKEN'] ?? '',
    'webhook_paths' => [
        'content' => __DIR__ . '/../content',
        // 'alias' => '/bezwzgledna/sciezka'
    ],
];
