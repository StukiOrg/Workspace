<?php

namespace StukiWorkspaceTest\Loader;

use StukiWorkspaceTest\Bootstrap
    , StukiWorkspace\Controller\IndexController
    , Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter
    , Zend\Http\Request
    , Zend\Http\Response
    , Zend\Mvc\MvcEvent
    , Zend\Mvc\Router\RouteMatch
    , PHPUnit_Framework_TestCase
    , Doctrine\ORM\Query\ResultSetMapping
    , Doctrine\ORM\Query\ResultSetMappingBuilder
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Driver\StaticPhpDriver
    , Doctrine\ORM\Mapping\Driver\PhpDriver
    , StukiWorkspace\Options\ModuleOptions
    , StukiWorkspace\Service\StukiWorkspaceService
    , StukiWorkspace\Loader\AuditAutoloader
    , StukiWorkspace\EventListener\LogRevision
    , StukiWorkspace\View\Helper\DateTimeFormatter
    , StukiWorkspace\View\Helper\EntityValues
    , Zend\ServiceManager\ServiceManager

    ;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    protected function setUp()
    {
    }

    public function testServiceManagerIsSet()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $this->assertInstanceOf('Zend\ServiceManager\ServiceManager', $sm);
    }

    public function testServiceConfig()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();

        $this->assertInstanceOf('StukiWorkspace\Options\ModuleOptions', $sm->get('auditModuleOptions'));
        $this->assertInstanceOf('StukiWorkspace\Service\StukiWorkspaceService', $sm->get('stukiWorkspaceService'));
    }

    public function testViewHelperConfig()
    {

        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('stukiDateTimeFormatter');

        $now = new \DateTime();
        $helper->setDateTimeFormat('U');
        $this->assertEquals($helper($now), $now->format('U'));
    }
}
