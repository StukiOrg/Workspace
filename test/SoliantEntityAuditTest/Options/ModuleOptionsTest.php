<?php

namespace WorkspaceTest\Options;

use Workspace\Options\ModuleOptions
    , Workspace\Tests\Util\ServiceManagerFactory
    , WorkspaceTest\Bootstrap
    ;

class ModuleOptionsTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    public function testModuleOptionDefaults()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        // For testing do not modify the di instance
        $moduleOptions = clone $serviceManager->get('workspaceModuleOptions');
        $moduleOptions->setDefaults(array());

        $this->assertEquals(20, $moduleOptions->getPaginatorLimit());
        $this->assertEquals('', $moduleOptions->getTableNamePrefix());
        $this->assertEquals('_workspace', $moduleOptions->getTableNameSuffix());
        $this->assertEquals('Revision', $moduleOptions->getRevisionTableName());
        $this->assertEquals('RevisionEntity', $moduleOptions->getRevisionEntityTableName());
        $this->assertEquals('revision', $moduleOptions->getRevisionFieldName());
        $this->assertEquals('revisionEntity', $moduleOptions->getRevisionEntityFieldName());
        $moduleOptions->setUserEntityClassName($serviceManager->get('zfcuser_module_options')->getUserEntityClass());
        $moduleOptions->setAuthenticationService('ZfcUserDoctrineORM\\Entity\\User');
        $moduleOptions->setWorkspaceService($serviceManager->get('workspaceService'));
    }

    public function testModuleOptionsWorkspaceEntityClasses()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        // For testing do not modify the di instance
        $moduleOptions = clone $serviceManager->get('workspaceModuleOptions');
        $moduleOptions->setDefaults(array());

        $moduleOptions->setWorkspaceClassNames(array('Test1', 'Test2'));
        $this->assertEquals($moduleOptions->getWorkspaceClassNames(), array('Test1', 'Test2'));
    }

    public function testSetUser()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $moduleOptions = clone $serviceManager->get('workspaceModuleOptions');
        $moduleOptions->setDefaults(array());

        $userClass = \Workspace\Module::getModuleOptions()->getUserEntityClassName();
        $user = new $userClass;

        $user->setEmail('test');
        $user->setPassword('test');

        $em->persist($user);
        $em->flush();

        $moduleOptions->setUser($user);

        $this->assertEquals($user, $moduleOptions->getUser());
    }

    // Hard to test: just test setter
    public function testSetJoinClass()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        $moduleOptions = clone $serviceManager->get('workspaceModuleOptions');
        $moduleOptions->setDefaults(array());

        $this->assertEquals($moduleOptions, $moduleOptions->addJoinClass('test', array()));
    }
}
