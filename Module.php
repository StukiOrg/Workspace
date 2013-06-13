<?php

namespace Workspace;

use Zend\Mvc\MvcEvent
    , Workspace\Options\ModuleOptions
    , Workspace\Service\WorkspaceService
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
        $moduleOptions = $e->getApplication()->getServiceManager()->get('workspaceModuleOptions');

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
                'workspaceModuleOptions' => function($serviceManager){
                    $config = $serviceManager->get('Application')->getConfig();
                    $workspaceConfig = new ModuleOptions();
                    $workspaceConfig->setDefaults($config['workspace']);
                    $workspaceConfig->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
                    $workspaceConfig->setWorkspaceService($serviceManager->get('workspaceService'));

                    $authenticationServiceAlias = (isset($config['workspace']['authenticationService'])) ? $config['workspace']['authenticationService']: 'zfcuser_auth_service';

                    $auth = $serviceManager->get($authenticationServiceAlias);
                    $workspaceConfig->setAuthenticationService($auth);

                    return $workspaceConfig;
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
                    $format = $config['workspace']['datetimeFormat'];
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
