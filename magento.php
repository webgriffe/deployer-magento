<?php

namespace Deployer;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputOption;

// TODO Add deployer version check (now it works only with Deployer >= 5.0)

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

// Magento media pull exclude dirs (paths must be relative to the media dir)
set('media_pull_exclude_dirs', ['css', 'css_secure', 'js']);

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
desc('Run the Magento setup scripts');
task('magento:setup-run', function () {
    $command = [
        'cd {{release_path}}/{{magento_root_path}}',
        'test -f app/etc/local.xml',
        'cat app/etc/local.xml | grep -q "<date>"',
        'n98-magerun.phar sys:setup:run'
    ];
    run(implode(' && ', $command));
});

desc('Clear Magento cache');
task('magento:clear-cache', function () {
    $command = [
        'cd {{release_path}}/{{magento_root_path}}',
        'test -f app/etc/local.xml',
        'cat app/etc/local.xml | grep -q "<date>"',
        'n98-magerun.phar cache:clean'
    ];
    run(implode(' && ', $command));
});

desc('Create Magento database dump');
task('magento:db-dump', function () {
    run('cd {{current_path}}/{{magento_root_path}} && n98-magerun.phar db:dump -n -c gz ~');
});

desc('Pull Magento database to local');
task('magento:db-pull', function () {
    $fileName = uniqid('dbdump_');
    $remoteDump = "/tmp/{$fileName}.sql.gz";
    run('cd {{current_path}}/{{magento_root_path}} && n98-magerun.phar db:dump -n -c gz ' . $remoteDump);
    $localDump =  tempnam(sys_get_temp_dir(), 'deployer_') . '.sql.gz';
    download($remoteDump, $localDump);
    run('rm ' . $remoteDump);
    runLocally('cd ./{{magento_root_path}} && n98-magerun.phar db:import -n -c gz ' . $localDump);
    runLocally('cd ./{{magento_root_path}} && n98-magerun.phar cache:disable');
    runLocally('rm ' . $localDump);
});

option(
    'media-pull-timeout',
    null,
    InputOption::VALUE_OPTIONAL,
    'Timeout for media-pull task in seconds (default is 300s)'
);
desc('Pull Magento media to local');
task('magento:media-pull', function () {
    $remotePath = '{{current_path}}/{{magento_root_path}}/media/';
    $localPath = './{{magento_root_path}}/media/';

    $excludeDirs = array_map(function($dir) {
        return '--exclude '.$dir;
    }, get('media_pull_exclude_dirs'));

    $timeout = 300;
    if (input()->hasOption('media-pull-timeout')) {
        $timeout = input()->getOption('media-pull-timeout');
    }
    $config = [
        'options' => $excludeDirs,
        'timeout' => $timeout
    ];


    download($remotePath, $localPath, $config);
});

desc('Set "copy" as Magento deploy strategy');
task('magento:set-copy-deploy-strategy', function(){
    run('cd {{release_path}} && {{bin/composer}} config extra.magento-deploystrategy copy');
    run('cd {{release_path}} && {{bin/composer}} config extra.magento-force true');
});

after('deploy:shared', 'magento:set-copy-deploy-strategy');
before('deploy:symlink', 'magento:setup-run');
after('deploy:symlink', 'magento:clear-cache');
