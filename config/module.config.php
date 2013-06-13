<?php

namespace Workspace;

return array(
    'doctrine' => array(
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'Workspace\Mapping\Driver\WorkspaceDriver',
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
                    'Workspace\EventListener\LogRevision',
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'workspace' => 'Workspace\Controller\IndexController'
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'workspaceCurrentRevisionEntity' => 'Workspace\View\Helper\CurrentRevisionEntity',
            'workspaceEntityOptions' => 'Workspace\View\Helper\EntityOptions',

            'workspaceRevisionEntityLink' => 'Workspace\View\Helper\RevisionEntityLink',

            'workspacePaginator' => 'Workspace\View\Helper\WorkspacePaginator',
            'workspaceRevisionPaginator' => 'Workspace\View\Helper\RevisionPaginator',
            'workspaceRevisionEntityPaginator' => 'Workspace\View\Helper\RevisionEntityPaginator',
            'workspaceAssociationSourcePaginator' => 'Workspace\View\Helper\AssociationSourcePaginator',
            'workspaceAssociationTargetPaginator' => 'Workspace\View\Helper\AssociationTargetPaginator',
            'workspaceOneToManyPaginator' => 'Workspace\View\Helper\OneToManyPaginator',
        ),
    ),

    'router' => array(
        'routes' => array(
            'workspace' => array(
                'type' => 'Literal',
                'priority' => 1000,
                'options' => array(
                    'route' => '/workspace',
                    'defaults' => array(
                        'controller' => 'workspace',
                        'action'     => 'master',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/page[/:page]',
                            'constraints' => array(
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'workspace',
                                'action'     => 'master',
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
                                'controller' => 'workspace',
                                'action'     => 'user',
                            ),
                        ),
                    ),
                    'master' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/master',
                            'defaults' => array(
                                'controller' => 'workspace',
                                'action'     => 'master',
                            ),
                        ),
                    ),
                    'firehose' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/firehose[/:page]',
                            'defaults' => array(
                                'controller' => 'workspace',
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
                                'controller' => 'workspace',
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
                                'controller' => 'workspace',
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
                                'controller' => 'workspace',
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
                                'controller' => 'workspace',
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
                                'controller' => 'workspace',
                                'action'     => 'association-source',
                            ),
                        ),
                    ),
                    'entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/entity[/:entityClass][/:page]',
                            'defaults' => array(
                                'controller' => 'workspace',
                                'action'     => 'entity',
                            ),
                        ),
                    ),
                    'compare' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/compare',
                            'defaults' => array(
                                'controller' => 'workspace',
                                'action' => 'compare',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
