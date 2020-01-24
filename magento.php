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
set(
    'shared_dirs',
    ['{{magento_root_path}}' . 'var', '{{magento_root_path}}' . 'media', '{{magento_root_path}}' . 'sitemaps']
);

// Magento shared files
set('shared_files', ['{{magento_root_path}}' . 'app/etc/local.xml', '{{magento_root_path}}' . '.htaccess']);

// Magento writable dirs
set(
    'writable_dirs',
    ['{{magento_root_path}}' . 'var', '{{magento_root_path}}' . 'media', '{{magento_root_path}}' . 'sitemaps']
);

// Magento media pull exclude dirs (paths must be relative to the media dir)
set('media_pull_exclude_dirs', ['css', 'css_secure', 'js', 'catalog/product/cache']);

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

// Setup run timeout
set('setup-run-timeout', 300);

// DB pull strip tables
set('db_pull_strip_tables', ['@stripped']);

// Local/remote magerun path
set('magerun_remote', 'n98-magerun.phar');
set('magerun_local', getenv('DEPLOYER_MAGERUN_LOCAL') ?: 'n98-magerun.phar');

// Tasks
desc('Run the Magento setup scripts');
task('magento:setup-run', function () {
    if (test('[ -f {{release_path}}/{{magento_root_path}}app/etc/local.xml ]')) {
        $installed = run('cat {{release_path}}/{{magento_root_path}}app/etc/local.xml | grep "<date>"; true');
        if ($installed) {
            run(
                'cd {{release_path}}/{{magento_root_path}} && {{magerun_remote}} sys:setup:run --no-implicit-cache-flush',
                ['timeout' => get('setup-run-timeout')]
            );
        }
    }
});

desc('Clear Magento cache');
task('magento:clear-cache', function () {
    if (test('[ -f {{release_path}}/{{magento_root_path}}app/etc/local.xml ]')) {
        $installed = run('cat {{release_path}}/{{magento_root_path}}app/etc/local.xml | grep "<date>"; true');
        if ($installed) {
            run('cd {{release_path}}/{{magento_root_path}} && {{magerun_remote}} cache:clean');
        }
    }
});

desc('Create Magento database dump');
task('magento:db-dump', function () {
    run('cd {{current_path}}/{{magento_root_path}} && {{magerun_remote}} db:dump -n -c gz ~');
});

option(
    'db-pull-timeout',
    null,
    InputOption::VALUE_OPTIONAL,
    'Timeout for db:dump and db:import executions in db-pull task in seconds (default is 300s)'
);
desc('Pull Magento database to local');
task('magento:db-pull', function () {
    $timeout = 300;
    if (input()->hasOption('db-pull-timeout')) {
        $timeout = input()->getOption('db-pull-timeout');
    }

    $fileName = uniqid('dbdump_');
    $stripTables = implode(' ', get('db_pull_strip_tables'));
    $remoteDump = "/tmp/{$fileName}.sql.gz";
    run(
        'cd {{current_path}}/{{magento_root_path}} && ' .
        '{{magerun_remote}} db:dump -n --strip="'. $stripTables .'" -c gz ' . $remoteDump,
        ['timeout' => $timeout]
    );
    $dumpName = tempnam(sys_get_temp_dir(), 'deployer_');
    $localDumpGz =  $dumpName . '.sql.gz';
    $localDumpSql =  $dumpName . '.sql';
    download($remoteDump, $localDumpGz);
    run('rm ' . $remoteDump);
    runLocally('gunzip ' . $localDumpGz);
    runLocally(
        'cd ./{{magento_root_path}} && {{magerun_local}} db:import -n --drop-tables --optimize ' . $localDumpSql,
        ['timeout' => $timeout]
    );

    runLocally('cd ./{{magento_root_path}} && {{magerun_local}} cache:disable');
    runLocally('rm ' . $localDumpSql);
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
