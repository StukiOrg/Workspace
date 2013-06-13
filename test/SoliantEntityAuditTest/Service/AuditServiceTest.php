<?php

namespace WorkspaceTest\Service;

use WorkspaceTest\Bootstrap
    , WorkspaceTest\Models\Bootstrap\Album
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    ;

class WorkspaceServiceTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the workspace driver has worked
    public function testCommentingAndCommentRestting()
    {
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $service->setComment('Test comment is reset when read');
        $this->assertEquals('Test comment is reset when read', $service->getComment());
        $this->assertEquals(null, $service->getComment());
    }

    public function testRevisionComment()
    {
        // Inserting data insures we will have a result > 0
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $entity = new Album;
        $entity->setTitle('Test 1');

        $service->setComment('Test service comment is persisted on revision');
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();
        $this->assertEquals('Test service comment is persisted on revision', $service->getComment());

return;
        $em->persist($entity);
        $em->flush();

        $x = $em->getRepository('Workspace\\Entity\\Revision')->findAll();
        print_r($x);

        $em->persist($entity);
        $em->flush();

        $helper = Bootstrap::getApplication()->getServiceManager()->get('viewhelpermanager')->get('workspaceCurrentRevisionEntity');
        $lastEntityRevision = $helper($entity);

        print_r($lastEntityRevision->getRevision());die();

        $this->assertEquals('test 2', $lastEntityRevision->getRevision()->getComment());
    }

    public function testGetEntityValues() {
        // Inserting data insures we will have a result > 0
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');

        $this->assertEquals(array('id' => null, 'title' => 'Test 1'), $service->getEntityValues($entity));
    }

    public function testGetRevisionEntities() {
        // Inserting data insures we will have a result > 0
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');

        $em->flush();

        $this->assertEquals(2, sizeof($service->getRevisionEntities($entity)));
    }

    public function testGetRevisionEntitiesByRevisionEntity()
    {
        // Inserting data insures we will have a result > 0
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');

        $em->flush();

        $serviceEntities = $service->getRevisionEntities($entity);

        $this->assertEquals(2, sizeof($service->getRevisionEntities(array_shift($serviceEntities)->getWorkspaceEntity())));

    }

    public function testGetRevisionEntitiesByEntityClass()
    {
        // Inserting data insures we will have a result > 0
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();
        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        $service->setComment('test 2');

        $entity = new Album;
        $entity->setTitle('Test 1');

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('Test 2');

        $em->flush();

        $serviceEntities = $service->getRevisionEntities($entity);

        $this->assertGreaterThan(1, sizeof($service->getRevisionEntities(get_class($entity))));

    }

}
