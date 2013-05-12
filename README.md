Stuki Workspace
===============

[![Build Status](https://travis-ci.org/StukiOrg/Workspace.png)](https://travis-ci.org/StukiOrg/Workspace)

A workspace module for Doctrine 2.  This module creates an entity to audit specified targets for every user to work within their own workspace.  Users may request their workspace be merged into master from any revision.  

About
=====

This module takes a configuration of entities to include in the workspace and creates 
entities to audit them.  The audited entites become a workspace for each user.  As the user creates, updates, and deletes data the changes are logged to their workspace.  The user may request their workspace be merged to master.  Merge requests are handled by privileged users.  As revisions are approved they are merged into the master database.

This is accomplished by evaluating each entity when it is fetched from the ORM.  The entity will be changed to the latest revision for the current user within their workspace.  


Install
=======

Require Stuki Workspace with composer 

```php
php composer.phar require "stuki/workspace": "dev-master"
```


Enable Stuki Workspace in `config/application.config.php`: 
```php
return array(
    'modules' => array(
        'StukiWorkspace'
        ...
    ),
```

Copy `config/workspace.global.php.dist` to `config/autoload/workspace.global.php` and edit setting as

```php
return array(
    'stuki' => array(
        'workspace' => array(
            'datetimeFormat' => 'r',
            'paginatorLimit' => 20,

            'tableNamePrefix' => '',
            'tableNameSuffix' => '_audit',
            'revisionTableName' => 'Revision',
            'revisionEntityTableName' => 'RevisionEntity',

            'entities' => array(           
                'Db\Entity\Song' => array(),
                'Db\Entity\Performer' => array(),
            ),
        ),
    ),
);
```

Use the Doctrine command line tool to update the database and create the auditing tables:

```shell
vendor/bin/doctrine-module orm:schema-tool:update
```


Terminology
-----------

AuditEntity - A generated entity which maps to the Target auditable entity.  This stores the values for the Target entity at the time a Revision is created.

Revision - An entity which stores the timestap, comment, an user for a single entity manager flush which contains auditable entities.

RevisionEntity - A mapping entity which maps an AuditEntity to a Revision and maps to a Target audited entity.  This entity can rehydrate a Target entity and AuditEntity.  This also stores the revision type when the Target was audited.  INS, UPD, and DEL map to insert, update, and delete.  The primary keys of the Target are stored as an array and can be used to rehydrate a Target.

Target entity - An auditable entity specified as string in the audit configuration.


Authentication 
--------------

You may configure a custom entity to serve as the user entity for mapping revisions to users.  You may configure a custom authentication service too.  By default these map to ZfcUserDoctrineORM\Entity\User and zfcuser_auth_service.  For example to use a custom entity and service Db\Entity\User for an entity and Zend\Authentication\AuthenticationService would work.

The user entity must implement getDisplayName, getId, and getEmail.  The authentication service must implement hasIdentity and getIdentity which returns an instance of the current user entity.

Interfaces are not used so ZfcUser can be used out of the box.


Routing
-------

To map a route to an audited entity include route information in the audit => entities config

```
    'Db\Entity\Song' => array(
        'route' => 'default',
        'defaults' => array(
            'controller' => 'song',
            'action' => 'detail',
        ),
    ),
```

Identifier column values from the audited entity will be added to defaults to generate urls through routing.

```
    <?php
        $options = $this->stukiEntityOptions($revisionEntity->getTargetEntityClass());
        $routeOptions = array_merge($options['defaults'], $revisionEntity->getEntityKeys());
    ?>
    <a class="btn btn-info" href="<?=
        $this->url($options['route'], $routeOptions);
    ?>">Data</a>
```

This is how to map from your application to it's current revision entity:

```
    <a class="btn btn-info" href="<?=
        $this->url('stuki-workspace/revision-entity',
            array(
                'revisionEntityId' => $this->stukiCurrentRevisionEntity($auditedEntity)->getId()
            )
        );
    ?>">
        <i class="icon-globe"></i>
    </a>
```


View Helpers
------------

Return the audit service.  This is a helper class.  The class is also available via dependency injection factory ```stukiWorkspaceService```
This class provides the following:

1. setComment();
    Set the comment for the next audit transaction.  When a comment is set it will be read at the time the audit Revision is created and added as the comment.

2. getAuditEntityValues($auditEntity);
    Returns all the fields and their values for the given audit entity.  Does not include many to many relations.

3. getEntityIdentifierValues($entity);
    Return all the identifying keys and values for an entity.
    
4. getRevisionEntities($entity)
    Returns all RevisionEntity entities for the given audited entity or RevisionEntity.
    
````
$view->stukiWorkspaceService();
```

Return the latest revision entity for the given entity.
```
$view->auditCurrentRevisionEntity($entity);
```

Return a paginator for all revisions of the specified class name.
```
$view->auditEntityPaginator($page, $entityClassName);
```

Return a paginator for all RevisionEntity entities for the given entity or 
a paginator attached to every RevisionEntity for the given audited entity class.Pass an entity or a class name string.
```
$view->stukiRevisionEntityPaginator($page, $entity);
```

Return a paginator for all Revision entities.
```
$view->stukiRevisionPaginator($page);
```

Returns the routing information for an entity by class name
```
$view->stukiEntityOptions($entityClassName);
```

Titling
-------

If an entity has a __toString method it will be used to title an audit entity limited to 256 characters and stored in the RevisionEntity.


Inspired by SimpleThings
