<?php

namespace StukiWorkspaceTest;

return array(
    'audit' => array(
        'datetimeFormat' => 'r',
        'paginatorLimit' => 999999,

        'userEntityClassName' => 'ZfcUserDoctrineORM\Entity\User',
        'authenticationService' => 'zfcuser_auth_service',

        'tableNamePrefix' => '',
        'tableNameSuffix' => '_audit',
        'revisionTableName' => 'Revision',
        'revisionEntityTableName' => 'RevisionEntity',

        'entities' => array(
            'StukiWorkspaceTest\Models\Bootstrap\Album' => array(),
            'StukiWorkspaceTest\Models\Bootstrap\Performer' => array(),
            'StukiWorkspaceTest\Models\Bootstrap\Song' => array(),
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
            'StukiWorkspace_moduleDriver' => array(
                'class' => 'StukiWorkspace\Mapping\Driver\AuditDriver',
            ),

            __NAMESPACE__ . '_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\StaticPHPDriver',
                'paths' => array(
                    __DIR__ . '/../StukiWorkspaceTest/Models/Bootstrap',
                ),
            ),

            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Models' => __NAMESPACE__ . '_driver',
                    'StukiWorkspace\Entity' => 'StukiWorkspace_moduleDriver',
                ),
            ),
        ),
    ),
);

