<?php

namespace Deployer;
require 'recipe/common.php';

set('magento_root_path', function () {
    $magentoRoot = get('magento_root');
    return empty($magentoRoot) ? '' : (rtrim($magentoRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);   
});

// Magento shared dirs
set('shared_dirs', ['{{magento_root_path}}' . 'var', '{{magento_root_path}}' . 'media']);

// Magento shared files
set('shared_files', ['{{magento_root_path}}' . 'app/etc/local.xml', '{{magento_root_path}}' . '.htaccess']);

// Magento writable dirs
set('writable_dirs', ['{{magento_root_path}}' . 'var', '{{magento_root_path}}' . 'media']);

// Magento clear paths
set(
    'clear_paths',
    [
        '{{magento_root_path}}' . 'LICENSE.html',
        '{{magento_root_path}}' . 'LICENSE.txt',
        '{{magento_root_path}}' . 'LICENSE_AFL.txt',
        '{{magento_root_path}}' . 'RELEASE_NOTES.txt',
        '{{magento_root_path}}' . 'downloader',
    ]
);

// Tasks
desc('Run the setup scripts');
task('deploy:setup', function () {
    run('cd {{release_path}}/{{magento_root_path}} && n98-magerun.phar sys:setup:run');
});

desc('Clear Magento cache');
task('magento:clear-cache', function () {
    run('cd {{current_path}}/{{magento_root_path}} && n98-magerun.phar cache:clean');
});

desc('Create Magento database dump');
task('magento:db-dump', function () {
    run('cd {{current_path}}/{{magento_root_path}} && n98-magerun.phar db:dump -n -c gz ~');
});

desc('Pull Magento database to local');
task('magento:db-pull', function () {
    $remoteDump = '/tmp/tmp.sql.gz';
    run('cd {{current_path}}/{{magento_root_path}} && n98-magerun.phar db:dump -n -c gz ' . $remoteDump);
    $localDump =  tempnam(sys_get_temp_dir(), 'deployer_') . '.sql.gz';
    download($localDump, $remoteDump);
    runLocally('cd {{magento_root_path}} && n98-magerun.phar db:import -n -c gz ' . $localDump);
});

desc('Deploy Magento Project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:setup',
    'deploy:symlink',
    'magento:clear-cache',
    'deploy:unlock',
    'cleanup',
    'success'
]);
