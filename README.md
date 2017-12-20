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

Magento useful tasks
--------------------

This recipe provides Magento useful tasks:

* `magento:db-dump`: creates a gzipped database dump on the remote stage in the deploy user's home directory
* `magento:db-pull`: pulls database from the remote stage to local environment
* `magento:media-pull`: pulls Magento media from the remote stage to local environment
  * With the `media_pull_exclude_dirs` environment variable it's possible to specify which sub-directories of the media dir you want to exclude. The `js` and `css` directories are excluded by default. Usage example:
    
    ```php
    add('media_pull_exclude_dirs', ['wysiwyg']);
    ```
* `magento:set-copy-deploy-strategy`: sets the "copy" deploy strategy for [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer) into the composer.json file.

License
-------

This library is under the MIT license. See the complete license in the LICENSE file.

Credits
-------

Developed by [Webgriffe®](http://www.webgriffe.com/). Please, report to us any bug or suggestion by GitHub issues.
