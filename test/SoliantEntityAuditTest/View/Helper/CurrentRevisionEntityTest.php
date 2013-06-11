<?php

namespace WorkspaceTest\View\Helper;

use WorkspaceTest\Bootstrap
    , WorkspaceTest\Models\Bootstrap\Album
    ;

class CurrentRevisionEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $entity = new Album();
        $entity->setTitle('Test CurrentRevisionEntity View Helper returns revision with more than two entities');

        $em->persist($entity);
        $em->flush();

        $revisionEntity = $helper($entity);

        // Test getRevisionEntities on Revision
        $this->assertEquals(1, sizeof($revisionEntity->getRevision()->getRevisionEntities()));

        $this->assertInstanceOf('Workspace\Entity\RevisionEntity', $revisionEntity);
    }

    public function testDoesNotReturnRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $entity = new Album();

        $revisionEntity = $helper($entity);

        $this->assertEquals(null, $revisionEntity);

    }
}
