ZF2EntityAudit
==============

An module to audit Doctrine 2 entities in ZF2 and browse the audit log


Demo
----
A demo application is saved in this [repository] , See the README on that repository  for installation instructions.

A working demo is online at http://bit.ly/ZF2EntityAudit

Documenation
------------

1. download ZF2EntityAudit from composer 
```php
php composer.phar require "tawfekov/zf2entityaudit": "dev-master"
```
or by adding it to `composer.json` then using `php composer.phar update` to install it 

2. enable ZF2EntityAudit in `config/application.config.php` to be like this : 
```php
return array(
    'modules' => array(
        'ZF2EntityAudit'
        ...
    ),
```

3. copy `config/zf2entityaudit.global.php.dist` to `config/autoload/zf2entityaudit.global.php` and edit
```php
<?php
return array(
    'zf2-entity-audit' => array(    
        'entities' => array(
            "Application\Entity\Entity1",
            "Application\Entity\Entity2",
            "Application\Entity\Entity3",
        ),
        'zfcuser.integration' => true,
    )
);
```
ZFCUser integration will assign the current user's email address  to the revision log else 'Anonymous' is used.

4. use doctrine command line tool to update the database and created the auditing tables :
```shell
vendor/bin/doctrine-module orm:schema-tool:update
```

5. viewing the auditing records :
Routes are added from /audit  This module provides the view layer for browsing the audit log
from this route.
in production server you can protect this interface by using any ACL module out there , I'd suggest using [BjyAuthorize].

6. if you find any problems or have ideas to improve this module please let me know

on Github : [tawfekov]
on Twitter: [@tawfekov]

Thanks also flys to [TomHAnderson] for his [Contributions].

[repository]:https://github.com/tawfekov/ZF2EntityAudit-demo
[Contributions]:https://github.com/tawfekov/ZF2EntityAudit/graphs/contributors
[BjyAuthorize]:github.com/bjyoungblood/BjyAuthorize
[TomHAnderson]:https://github.com/TomHAnderson
[tawfekov]:https://github.com/tawfekov
[@tawfekov]:http://twitter.com/tawfekov
