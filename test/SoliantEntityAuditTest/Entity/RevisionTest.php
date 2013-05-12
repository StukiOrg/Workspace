<?php

namespace StukiWorkspaceTest\Entity;

use StukiWorkspaceTest\Bootstrap
    , StukiWorkspace\Entity\Revision
    , Doctrine\Common\Persistence\Mapping\ClassMetadata
    ;

class RevisionTest extends \PHPUnit_Framework_TestCase
{

    // If we reach this function then the audit driver has worked
    public function testGettersAndSetters()
    {
        $entity = new Revision;

        $this->assertLessThanOrEqual(new \DateTime(), $entity->getTimestamp());

        $userClass = \StukiWorkspace\Module::getModuleOptions()->getUserEntityClassName();
        $user = new $userClass;

        $this->assertEquals($entity, $entity->setUser($user));

        $this->assertEquals($user, $entity->getUser());

        $entity->setComment('Test revision entity setter and getter');
        $this->assertEquals('Test revision entity setter and getter', $entity->getComment());
    }
}
