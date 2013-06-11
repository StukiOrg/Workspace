<?php

namespace Workspace;

use Zend\Mvc\MvcEvent
    , Workspace\Options\ModuleOptions
    , Workspace\Service\WorkspaceService
    , Workspace\Loader\AuditAutoloader
    , Workspace\EventListener\LogRevision
    , Workspace\View\Helper\DateTimeFormatter
    , Workspace\View\Helper\EntityValues
    ;

class Module
{
    private static $moduleOptions;

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),

            'Workspace\Loader\WorkspaceAutoloader' => array(
                'namespaces' => array(
                    'Workspace\Entity' => __DIR__,
                )
            ),
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $moduleOptions = $e->getApplication()->getServiceManager()->get('auditModuleOptions');

        self::setModuleOptions($moduleOptions);
    }

    public static function setModuleOptions(ModuleOptions $moduleOptions)
    {
        self::$moduleOptions = $moduleOptions;
    }

    public static function getModuleOptions()
    {
        return self::$moduleOptions;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'auditModuleOptions' => function($serviceManager){
                    $config = $serviceManager->get('Application')->getConfig();
                    $auditConfig = new ModuleOptions();
                    $auditConfig->setDefaults($config['workspace']);
                    $auditConfig->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
                    $auditConfig->setWorkspaceService($serviceManager->get('workspaceService'));

                    $authenticationServiceAlias = (isset($config['workspace']['authenticationService'])) ? $config['workspace']['authenticationService']: 'zfcuser_auth_service';

                    $auth = $serviceManager->get($authenticationServiceAlias);
                    $auditConfig->setAuthenticationService($auth);

                    return $auditConfig;
                },

                'workspaceService' => function($sm) {
                    return new WorkspaceService();
                }
            ),
        );
    }

    public function getViewHelperConfig()
    {
         return array(
            'factories' => array(
                'WorkspaceDateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['audit']['datetimeFormat'];
                    $formatter = new DateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                },

                'workspaceService' => function($sm) {
                    return new WorkspaceService();
                }
            )
        );
    }
}
