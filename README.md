Magento 1.x Deployer Recipe
===========================

Deployer recipe for Magento project. It requires Deployer greater or equal to version `5.0`.

Install
-------

Install it using Composer:

	$ composer require --dev webgriffe/deployer-magento
	
Usage
-----

Require the recipe in your `deploy.php`:

```php

namespace Deployer;

require __DIR__ . '/vendor/webgriffe/deployer-magento/magento.php';

// Set magento root directory inside release path (leave blank if Magento is in the root of the release path)
set('magento_root', 'magento');

// ... usual Deployer configuration
```

Configuration
-------------

This recipe provides the following Deployer parameters that you can set in your local `deploy.php` file:

* `media_pull_exclude_dirs`, default value `['css', 'css_secure', 'js']`: allows to set a list of subdirectories of the `media` folder that will be excluded from the `magento:media-pull` task.
* `setup-run-timeout`, default value `300`: allows to set the timeout for the `magento:setup-run` task which in some cases takes more time.
* `db_pull_strip_tables`, default value `['@stripped']`: allows to set an array of table names or groups which data will be stripped from the database dump generated with the `magento:db-pull` task. Table names or groups syntax follow the same rules of the `--strip` option of the `n98-magerun.phar db:dump`, see the [magerun](https://github.com/netz98/n98-magerun) documentation for more information.
* `magerun_remote`, default value `n98-magerun.phar`: allows to set the path of the magerun bin on the remote stage.
* `magerun_local`, default value `getenv('DEPLOYER_MAGERUN_LOCAL') ?: 'n98-magerun.phar'`: allows to set the path of the local magerun bin. As you can see the default value is taken from the `DEPLOYER_MAGERUN_LOCAL ` environment variable if it's set, otherwise `n98-magerun.phar` will be used.


Magento useful tasks
--------------------

This recipe provides Magento useful tasks:

* `magento:db-dump`: creates a gzipped database dump on the remote stage in the deploy user's home directory
* `magento:db-pull`: pulls database from the remote stage to local environment  
* `magento:media-pull`: pulls Magento media from the remote stage to local environment  
* `magento:set-copy-deploy-strategy`: sets the "copy" deploy strategy for [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer) into the composer.json file.

License
-------

This library is under the MIT license. See the complete license in the LICENSE file.

Credits
-------

Developed by [Webgriffe®](http://www.webgriffe.com/). Please, report to us any bug or suggestion by GitHub issues.
