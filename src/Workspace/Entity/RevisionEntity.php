<?php

namespace Workspace\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Zend\Code\Reflection\ClassReflection;

class RevisionEntity
{
    private $id;

    // Foreign key to the revision
    private $revision;

    // An array of primary keys
    private $entityKeys;

    // The name of the workspace entity
    private $workspaceEntityClass;

    // The name of the entity which is workspaceed
    private $targetEntityClass;

    // The type of action, INS, UPD, DEL
    private $revisionType;

    // Fetched from entity::getWorkspaceTitle() if exists
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setRevision(Revision $revision)
    {
        $this->revision = $revision;
        return $this;
    }

    public function getWorkspaceEntityClass()
    {
        return $this->workspaceEntityClass;
    }

    public function setWorkspaceEntityClass($value)
    {
        $this->workspaceEntityClass = $value;
        return $this;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function setTargetEntityClass($value)
    {
        $this->targetEntityClass = $value;
        return $this;
    }

    public function getTargetEntityClass()
    {
        return $this->targetEntityClass;
    }

    public function getEntityKeys()
    {
        return unserialize($this->entityKeys);
    }

    public function setEntityKeys($value)
    {
        unset($value['revisionEntity']);

        $this->entityKeys = serialize($value);
    }

    public function getRevisionType()
    {
        return $this->revisionType;
    }

    public function setRevisionType($value)
    {
        $this->revisionType = $value;
        return $this;
    }

    public function setWorkspaceEntity(AbstractWorkspace $entity)
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();

        $workspaceService = $moduleOptions->getWorkspaceService();
        $identifiers = $workspaceService->getEntityIdentifierValues($entity);

        $this->setWorkspaceEntityClass(get_class($entity));
        $this->setTargetEntityClass($entity->getWorkspaceedEntityClass());
        $this->setEntityKeys($identifiers);

        return $this;
    }

    public function getWorkspaceEntity()
    {
        $entityManager = \Workspace\Module::getModuleOptions()->getEntityManager();

        return $entityManager->getRepository($this->getWorkspaceEntityClass())->findOneBy(array('revisionEntity' => $this));
    }

    public function getTargetEntity()
    {
        $entityManager = \Workspace\Module::getModuleOptions()->getEntityManager();

        return $entityManager->getRepository(
            $entityManager
                ->getRepository($this->getWorkspaceEntityClass())
                    ->findOneBy($this->getEntityKeys())->getWorkspaceedEntityClass()
            )->findOneBy($this->getEntityKeys());
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = substr($value, 0, 256);

        return $this;
    }
}
