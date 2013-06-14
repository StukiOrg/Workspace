<?php

namespace WorkspaceTest\Service;

use WorkspaceTest\Bootstrap
    , WorkspaceTest\Models\LogRevision\Album
    , WorkspaceTest\Models\LogRevision\Song
    , WorkspaceTest\Models\LogRevision\Performer
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\ORM\Tools\Setup
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\Driver\StaticPHPDriver
    , Doctrine\ORM\Mapping\Driver\XmlDriver
    , Doctrine\ORM\Mapping\Driver\DriverChain
    , Workspace\Mapping\Driver\WorkspaceDriver
    , Workspace\EventListener\LogRevision
    , Doctrine\ORM\Tools\SchemaTool
    ;

class LogRevisionTest extends \PHPUnit_Framework_TestCase
{
    private $_em;
    private $_oldEntityManager;

    public function setUp()
    {
        $this->_oldEntityManager = \Workspace\Module::getModuleOptions()->getEntityManager();
        $this->_oldWorkspaceClassNames = \Workspace\Module::getModuleOptions()->getWorkspaceClassNames();
        $this->_oldJoinClasses = \Workspace\Module::getModuleOptions()->resetJoinClasses();

        $isDevMode = false;

        $config = Setup::createConfiguration($isDevMode, null, null);

        $chain = new DriverChain();

        // Use ZFC User for authentication tests
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuser')
            , 'ZfcUser\Entity');
        $chain->addDriver(new XmlDriver(__DIR__ . '/../../../vendor/zf-commons/zfc-user-doctrine-orm/config/xml/zfcuserdoctrineorm')
            , 'ZfcUserDoctrineORM\Entity');
        $chain->addDriver(new StaticPHPDriver(__DIR__ . "/../Models"), 'WorkspaceTest\Models\LogRevision');
        $chain->addDriver(new WorkspaceDriver('.'), 'Workspace\Entity');

        $config->setMetadataDriverImpl($chain);

        // Replace entity manager
        $moduleOptions = \Workspace\Module::getModuleOptions();

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $moduleOptions->setWorkspaceClassNames(array(
            'WorkspaceTest\Models\LogRevision\Album' => array(),
            'WorkspaceTest\Models\LogRevision\Performer' => array(),
            'WorkspaceTest\Models\LogRevision\Song' => array(),
            'WorkspaceTest\Models\LogRevision\SingleCoverArt' => array(),
        ));

        $entityManager = EntityManager::create($conn, $config);
        $moduleOptions->setEntityManager($entityManager);
        $schemaTool = new SchemaTool($entityManager);

        // Add workspaceing listener
        $entityManager->getEventManager()->addEventSubscriber(new LogRevision());

        $sql = $schemaTool->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());
        #print_r($sql);die();

        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->_em = $entityManager;

    }

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

    public function testOneToManyWorkspace()
    {
        $album = new Album;
        $album->setTitle('Test One To Many Workspace');

        $song = new Song;
        $song->setTitle('Test one to many workspace song > album');

        $song->setAlbum($album);
        $album->getSongs()->add($song);

        $this->_em->persist($album);
        $this->_em->persist($song);

        $this->_em->flush();


        $persistedSong = $this->_em->getRepository('WorkspaceTest\Models\LogRevision\Song')->find($song->getId());

        $this->assertEquals($song, $persistedSong);
        $this->assertEquals($album, $persistedSong->getAlbum());
    }

    public function testManyToManyWorkspace()
    {
        $album = new Album;
        $album->setTitle('Test Many To Many Workspace');

        $performer = new Performer;
        $performer->setName('Test many to many workspace');

        $this->_em->persist($album);
        $this->_em->persist($performer);

        $this->_em->flush();

        $performer->getAlbums()->add($album);
        $album->getPerformers()->add($performer);

        $this->_em->flush();

        $moduleOptions = \Workspace\Module::getModuleOptions();
        $this->assertGreaterThan(0, sizeof($moduleOptions->getJoinClasses()));

        $manyToManys = $this->_em->getRepository('Workspace\Entity\performer_album')->findAll();
        $manyToMany = reset($manyToManys);

        $this->assertInstanceOf('Workspace\Entity\performer_album', $manyToMany);
#        $manyToManyValues = $manyToMany->getArrayCopy();

        $this->assertEquals($album->getId(), $manyToMany->getSourceRevisionEntity()->getTargetEntity()->getId());
        $this->assertEquals($performer->getId(), $manyToMany->getTargetRevisionEntity()->getTargetEntity()->getId());
    }

    public function testWorkspaceDeleteEntity()
    {
        $album = new Album;
        $album->setTitle('test workspace delete entity');
        $this->_em->persist($album);

        $this->_em->flush();

        $this->_em->remove($album);
        $this->_em->flush();
    }

    public function testCollectionDeletion()
    {
        $album = new Album;
        $album->setTitle('Test collection deletion');

        $performer = new Performer;
        $performer->setName('Test collection deletion');

        $performer->getAlbums()->add($album);
        $album->getPerformers()->add($performer);

        $this->_em->flush();

        $performer->getAlbums()->removeElement($album);
        $album->getPerformers()->removeElement($performer);

        $this->_em->flush();

        $manyToManys = $this->_em->getRepository('Workspace\Entity\performer_album')->findAll();

        $this->assertEquals(array(), $manyToManys);

    }

    public function tearDown()
    {
        // Replace entity manager
        $moduleOptions = \Workspace\Module::getModuleOptions();
        $moduleOptions->setEntityManager($this->_oldEntityManager);
        \Workspace\Module::getModuleOptions()->setWorkspaceClassNames($this->_oldWorkspaceClassNames);
        \Workspace\Module::getModuleOptions()->resetJoinClasses($this->_oldJoinClasses);
    }
}