Magento 1.x Deployer Recipe
===========================

Deployer recipe for Magento project.

Install
-------

Install it using Composer:

	$ composer require webgriffe/deployer-recipe dev-master
	
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

Database tasks
-----------------

This recipe provides useful database tasks:

* `magento:db-dump`: creates a gzipped database dump on the remote stage in the deploy user's home directory
* `magento:db-pull`: pulls database from the remote stage to local environment

License
-------

This library is under the MIT license. See the complete license in the LICENSE file.

Credits
-------

Developed by [Webgriffe®](http://www.webgriffe.com/). Please, report to us any bug or suggestion by GitHub issues.