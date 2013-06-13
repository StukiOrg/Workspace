<?php

namespace WorkspaceTest;

return array(
    'workspace' => array(
        'datetimeFormat' => 'r',
        'paginatorLimit' => 999999,

        'userEntityClassName' => 'ZfcUserDoctrineORM\Entity\User',
        'authenticationService' => 'zfcuser_auth_service',

        'tableNamePrefix' => '',
        'tableNameSuffix' => '_workspace',
        'revisionTableName' => 'Revision',
        'revisionEntityTableName' => 'RevisionEntity',

        'entities' => array(
            'WorkspaceTest\Models\Bootstrap\Album' => array(),
            'WorkspaceTest\Models\Bootstrap\Performer' => array(),
            'WorkspaceTest\Models\Bootstrap\Song' => array(),
        ),
    ),

    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'driverClass' => 'Doctrine\DBAL\Driver\PDOSqlite\Driver',
                'params' => array(
                    'user' => 'test',
                    'password' => 'test',
                    'memory' => true,
                ),
            ),
        ),

        'driver' => array(
            'Workspace_moduleDriver' => array(
                'class' => 'Workspace\Mapping\Driver\WorkspaceDriver',
            ),

            __NAMESPACE__ . '_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\StaticPHPDriver',
                'paths' => array(
                    __DIR__ . '/../WorkspaceTest/Models/Bootstrap',
                ),
            ),

            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Models' => __NAMESPACE__ . '_driver',
                    'Workspace\Entity' => 'Workspace_moduleDriver',
                ),
            ),
        ),
    ),
);

