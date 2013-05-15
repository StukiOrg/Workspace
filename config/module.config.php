<?php

namespace StukiWorkspace;

return array(
    'doctrine' => array(
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'StukiWorkspace\Mapping\Driver\AuditDriver',
            ),

            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                ),
            ),
        ),

        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(
                    'StukiWorkspace\EventListener\LogRevision',
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'audit' => 'StukiWorkspace\Controller\IndexController'
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'stukiCurrentRevisionEntity' => 'StukiWorkspace\View\Helper\CurrentRevisionEntity',
            'stukiEntityOptions' => 'StukiWorkspace\View\Helper\EntityOptions',

            'stukiRevisionEntityLink' => 'StukiWorkspace\View\Helper\RevisionEntityLink',

            'stukiWorkspacePaginator' => 'StukiWorkspace\View\Helper\WorkspacePaginator',
            'stukiRevisionPaginator' => 'StukiWorkspace\View\Helper\RevisionPaginator',
            'stukiRevisionEntityPaginator' => 'StukiWorkspace\View\Helper\RevisionEntityPaginator',
            'stukiAssociationSourcePaginator' => 'StukiWorkspace\View\Helper\AssociationSourcePaginator',
            'stukiAssociationTargetPaginator' => 'StukiWorkspace\View\Helper\AssociationTargetPaginator',
            'stukiOneToManyPaginator' => 'StukiWorkspace\View\Helper\OneToManyPaginator',
        ),
    ),

    'router' => array(
        'routes' => array(
            'stuki-workspace' => array(
                'type' => 'Literal',
                'priority' => 1000,
                'options' => array(
                    'route' => '/workspace',
                    'defaults' => array(
                        'controller' => 'audit',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:page]',
                            'constraints' => array(
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'index',
                                'page' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'user' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/user[/:userId][/:page]',
                            'constraints' => array(
                                'userId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'user',
                            ),
                        ),
                    ),
                    'master' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/master',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'master',
                            ),
                        ),
                    ),
                    'firehose' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/firehose',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'firehose',
                            ),
                        ),
                    ),
                    'revision' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision[/:revisionId]',
                            'constraints' => array(
                                'revisionId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revision',
                                'revisionId' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'revision-entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision-entity[/:revisionEntityId][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revisionEntity',
                            ),
                        ),
                    ),
                    'one-to-many' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/one-to-many[/:revisionEntityId][/:joinTable][/:mappedBy][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'one-to-many',
                            ),
                        ),
                    ),
                    'association-target' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/association-target[/:revisionEntityId][/:joinTable][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'association-target',
                            ),
                        ),
                    ),
                    'association-source' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/association-source[/:revisionEntityId][/:joinTable][/:page]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'association-source',
                            ),
                        ),
                    ),
                    'entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/entity[/:entityClass][/:page]',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'entity',
                            ),
                        ),
                    ),
                    'compare' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/compare',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action' => 'compare',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
