<?php

namespace WorkspaceTest\Loader;

use WorkspaceTest\Bootstrap
    , WorkspaceTest\Models\Autoloader\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\ORM\Tools\Setup
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\Driver\StaticPHPDriver
    , Doctrine\ORM\Mapping\Driver\XmlDriver
    , Doctrine\ORM\Mapping\Driver\DriverChain
    , Workspace\Mapping\Driver\WorkspaceDriver
    , Doctrine\ORM\Tools\SchemaTool
    ;

class WorkspaceAutoloaderTest extends \PHPUnit_Framework_TestCase
{
    private $_em;
    private $_oldEntityManager;
    private $_oldWorkspaceedClassNames;

    public function setUp()
    {
        $this->_oldEntityManager = \Workspace\Module::getModuleOptions()->getEntityManager();
        $this->_oldWorkspaceedClassNames = \Workspace\Module::getModuleOptions()->getWorkspaceedClassNames();


        $isDevMode = true;

        $config = Setup::createConfiguration($isDevMode, null, null);

        $chain = new DriverChain();
        // zfc user is required
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuser')
            , 'ZfcUser\Entity');
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuserdoctrineorm')
            , 'ZfcUserDoctrineORM\Entity');
        $chain->addDriver(new StaticPHPDriver(__DIR__ . "/../Models"), 'WorkspaceTest\Models\Autoloader');
        $chain->addDriver(new WorkspaceDriver('.'), 'Workspace\Entity');

        // Replace entity manager
        $moduleOptions = \Workspace\Module::getModuleOptions();
        $moduleOptions->setWorkspaceedClassNames(array(
            'WorkspaceTest\Models\Autoloader\Album' => array(),
            'WorkspaceTest\Models\Autoloader\Performer' => array(),
            'WorkspaceTest\Models\Autoloader\Song' => array(),
        ));


        $config->setMetadataDriverImpl($chain);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $entityManager = EntityManager::create($conn, $config);
        $moduleOptions->setEntityManager($entityManager);
        $schemaTool = new SchemaTool($entityManager);

        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->_em = $entityManager;

    }

    public function testTrue()
    {
        $this->assertTrue(true);
    }

/*
    // If we reach this function then the workspace driver has worked
    public function testWorkspaceCreateUpdateDelete()
    {
        $album = new Album;
        $album->setTitle('Test entity lifecycle: CREATE');

        $this->_em->persist($album);
        $this->_em->flush();

        $album->setTitle('Test entity lifecycle: UPDATE');

        $this->_em->flush();

        $album->setTitle('Test entity lifecycle: DELETE');

        $this->_em->flush();


        $this->assertTrue(true);
    }
*/
    public function tearDown()
    {
        // Replace entity manager
        $moduleOptions = \Workspace\Module::getModuleOptions();
        $moduleOptions->setEntityManager($this->_oldEntityManager);
        \Workspace\Module::getModuleOptions()->setWorkspaceedClassNames($this->_oldWorkspaceedClassNames);
    }
}