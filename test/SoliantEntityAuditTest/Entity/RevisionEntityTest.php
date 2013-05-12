<?php

namespace StukiWorkspaceTest\Entity;

use StukiWorkspaceTest\Bootstrap
    , StukiWorkspace\Entity\Revision
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    , StukiWorkspaceTest\Models\Bootstrap\Album
    ;

class RevisionEntityTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testGettersAndSetters()
    {        $em = Bootstrap::getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default");
        $sm = Bootstrap::getApplication()->getServiceManager();

        $entity = new Album;
        $entity->setTitle('test 1');

        $em->persist($entity);
        $em->flush();

        $helper = $sm->get('viewhelpermanager')->get('auditCurrentRevisionEntity');

        $revisionEntity = $helper($entity);

        $this->assertEquals('INS', $revisionEntity->getRevisionType());
        $this->assertEquals($entity, $revisionEntity->getTargetEntity());
        $this->assertEquals('StukiWorkspaceTest\Models\Bootstrap\Album', $revisionEntity->getTargetEntityClass());

    }
}
