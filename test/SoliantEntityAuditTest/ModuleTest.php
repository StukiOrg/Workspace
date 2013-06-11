<?php

namespace WorkspaceTest\Loader;

use WorkspaceTest\Bootstrap
    , Workspace\Controller\IndexController
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
    , Workspace\Options\ModuleOptions
    , Workspace\Service\WorkspaceService
    , Workspace\Loader\AuditAutoloader
    , Workspace\EventListener\LogRevision
    , Workspace\View\Helper\DateTimeFormatter
    , Workspace\View\Helper\EntityValues
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

        $this->assertInstanceOf('Workspace\Options\ModuleOptions', $sm->get('auditModuleOptions'));
        $this->assertInstanceOf('Workspace\Service\WorkspaceService', $sm->get('workspaceService'));
    }

    public function testViewHelperConfig()
    {

        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('WorkspaceDateTimeFormatter');

        $now = new \DateTime();
        $helper->setDateTimeFormat('U');
        $this->assertEquals($helper($now), $now->format('U'));
    }
}
