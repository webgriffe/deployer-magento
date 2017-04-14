<?php

namespace Deployer;
use Deployer\Task\Context;

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
desc('Run the Magento setup scripts');
task('magento:setup-run', function () {
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

desc('Pull Magento media to local');
task('magento:media-pull', function () {
    $serverConfig = Context::get()->getServer()->getConfiguration();
    $sshOptions = [
        '-A',
        '-o UserKnownHostsFile=/dev/null',
        '-o StrictHostKeyChecking=no'
    ];

    if (\Deployer\get('ssh_multiplexing', false)) {
        $this->initMultiplexing();
        $sshOptions = array_merge($sshOptions, $this->getMultiplexingSshOptions());
    }

    $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
    if (!empty($username)) {
        $username .= '@';
    }
    $hostname = $serverConfig->getHost();

    if ($serverConfig->getConfigFile()) {
        $sshOptions[] = '-F ' . escapeshellarg($serverConfig->getConfigFile());
    }

    if ($serverConfig->getPort()) {
        $sshOptions[] = '-p ' . escapeshellarg($serverConfig->getPort());
    }

    if ($serverConfig->getPrivateKey()) {
        $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
    } elseif ($serverConfig->getPemFile()) {
        $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPemFile());
    }

    if ($serverConfig->getPty()) {
        $sshOptions[] = '-t';
    }

    $sshCommand = 'ssh ' . implode(' ', $sshOptions);
    $remotePath = '{{current_path}}/{{magento_root_path}}/media/';

    runLocally(
        'cd {{magento_root_path}} && '.
        'rsync -arvuzi -e "'.$sshCommand.'" '.$username . $hostname.':'.$remotePath.' media/');
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
    'magento:setup-run',
    'deploy:symlink',
    'magento:clear-cache',
    'deploy:unlock',
    'cleanup',
    'success'
]);

desc('First Deploy for Magento Project (no Clear Cache and Setup Upgrades)');
task('magento:first-deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);
