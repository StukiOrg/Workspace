<?php

namespace StukiWorkspace;

use Zend\Mvc\MvcEvent
    , StukiWorkspace\Options\ModuleOptions
    , StukiWorkspace\Service\StukiWorkspaceService
    , StukiWorkspace\Loader\AuditAutoloader
    , StukiWorkspace\EventListener\LogRevision
    , StukiWorkspace\View\Helper\DateTimeFormatter
    , StukiWorkspace\View\Helper\EntityValues
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

            'StukiWorkspace\Loader\StukiWorkspaceAutoloader' => array(
                'namespaces' => array(
                    'StukiWorkspace\Entity' => __DIR__,
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
                    $auditConfig->setDefaults($config['audit']);
                    $auditConfig->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
                    $auditConfig->setStukiWorkspaceService($serviceManager->get('stukiWorkspaceService'));

                    $authenticationServiceAlias = (isset($config['audit']['authenticationService'])) ? $config['audit']['authenticationService']: 'zfcuser_auth_service';

                    $auth = $serviceManager->get($authenticationServiceAlias);
                    $auditConfig->setAuthenticationService($auth);

                    return $auditConfig;
                },

                'stukiWorkspaceService' => function($sm) {
                    return new StukiWorkspaceService();
                }
            ),
        );
    }

    public function getViewHelperConfig()
    {
         return array(
            'factories' => array(
                'auditDateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['audit']['datetimeFormat'];
                    $formatter = new DateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                },

                'stukiWorkspaceService' => function($sm) {
                    return new StukiWorkspaceService();
                }
            )
        );
    }
}
