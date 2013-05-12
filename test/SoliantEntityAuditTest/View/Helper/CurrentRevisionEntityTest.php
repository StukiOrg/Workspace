<?php

namespace StukiWorkspaceTest\View\Helper;

use StukiWorkspaceTest\Bootstrap
    , StukiWorkspaceTest\Models\Bootstrap\Album
    ;

class CurrentRevisionEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \StukiWorkspace\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $entity = new Album();
        $entity->setTitle('Test CurrentRevisionEntity View Helper returns revision with more than two entities');

        $em->persist($entity);
        $em->flush();

        $revisionEntity = $helper($entity);

        // Test getRevisionEntities on Revision
        $this->assertEquals(1, sizeof($revisionEntity->getRevision()->getRevisionEntities()));

        $this->assertInstanceOf('StukiWorkspace\Entity\RevisionEntity', $revisionEntity);
    }

    public function testDoesNotReturnRevisionEntity()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $em = \StukiWorkspace\Module::getModuleOptions()->getEntityManager();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $entity = new Album();

        $revisionEntity = $helper($entity);

        $this->assertEquals(null, $revisionEntity);

    }
}
