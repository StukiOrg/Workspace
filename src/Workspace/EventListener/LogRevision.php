<?php

namespace Workspace\EventListener;

use Doctrine\Common\EventSubscriber
    , Doctrine\ORM\Events
    , Doctrine\ORM\Event\OnFlushEventArgs
    , Doctrine\ORM\Event\OnLoadEventArgs
    , Doctrine\ORM\Event\PostFlushEventArgs
    , Doctrine\ORM\Event\LifecycleEventArgs
    , Workspace\Entity\Revision as RevisionEntity
    , Workspace\Options\ModuleOptions
    , Workspace\Entity\RevisionEntity as RevisionEntityEntity
    , Zend\Code\Reflection\ClassReflection
    , Doctrine\ORM\PersistentCollection
    ;

use \Doctrine\ORM\CancelLoadEntitiyException;

class LogRevision implements EventSubscriber
{
    private $revision;
    private $entities;
    private $reexchangeEntities;
    private $collections;
    private $inWorkspaceTransaction;
    private $many2many;

    public function getSubscribedEvents()
    {
        return array(
            Events::onFlush,
            Events::postFlush
        );
    }

    private function setEntities($entities)
    {
        if ($this->entities) return $this;
        $this->entities = $entities;

        return $this;
    }

    private function resetEntities()
    {
        $this->entities = array();
        return $this;
    }

    private function getEntities()
    {
        return $this->entities;
    }

    private function getReexchangeEntities()
    {
        if (!$this->reexchangeEntities) $this->reexchangeEntities = array();
        return $this->reexchangeEntities;
    }

    private function resetReexchangeEntities()
    {
        $this->reexchangeEntities = array();
    }

    private function addReexchangeEntity($entityMap)
    {
        $this->reexchangeEntities[] = $entityMap;
    }

    private function addRevisionEntity(RevisionEntityEntity $entity)
    {
        $this->revisionEntities[] = $entity;
    }

    private function resetRevisionEntities()
    {
        $this->revisionEntities = array();
    }

    private function getRevisionEntities()
    {
        return $this->revisionEntities;
    }

    public function addCollection($collection)
    {
        if (!$this->collections) $this->collections = array();
        if (in_array($collection, $this->collections)) return;
        $this->collections[] = $collection;
    }

    public function getCollections()
    {
        if (!$this->collections) $this->collections = array();
        return $this->collections;
    }

    public function setInWorkspaceTransaction($setting)
    {
        $this->inWorkspaceTransaction = $setting;
        return $this;
    }

    public function getInWorkspaceTransaction()
    {
        return $this->inWorkspaceTransaction;
    }

    private function getRevision()
    {
        return $this->revision;
    }

    private function resetRevision()
    {
        $this->revision = null;
        return $this;
    }

    // You must flush the revision for the compound workspace key to work
    private function buildRevision()
    {
        if ($this->revision) return;

        $revision = new RevisionEntity();
        $moduleOptions = \Workspace\Module::getModuleOptions();
        if ($moduleOptions->getUser()) {
            $revision->setUser($moduleOptions->getUser());
        } else {
            // If a user can modify data without being logged in approve it immediatly
            $revision->setApprove('approved');
            $revision->setApproveTimestamp(new \DateTime());
        }

        $comment = $moduleOptions->getWorkspaceService()->getComment();
        $revision->setComment($comment);

        $this->revision = $revision;
    }

    // Reflect workspaceed entity properties
    private function getClassProperties($entity)
    {
        $properties = array();

        $reflectedWorkspaceedEntity = new ClassReflection($entity);

        // Get mapping from metadata

        foreach($reflectedWorkspaceedEntity->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // If a property is an object we probably are not mapping that to
            // a field.  Do no special handing...
            if ($value instanceof PersistentCollection) {
            }

            // Set values to getId for classes
            if (gettype($value) == 'object' and method_exists($value, 'getId')) {
                $value = $value->getId();
            }

            $properties[$property->getName()] = $value;
        }

        return $properties;
    }

    private function workspaceEntity($entity, $revisionType)
    {
        $workspaceEntities = array();

        $moduleOptions = \Workspace\Module::getModuleOptions();
        if (!in_array(get_class($entity), array_keys($moduleOptions->getWorkspaceedClassNames())))
            return array();

        $workspaceEntityClass = 'Workspace\\Entity\\' . str_replace('\\', '_', get_class($entity));
        $workspaceEntity = new $workspaceEntityClass();
        $workspaceEntity->exchangeArray($this->getClassProperties($entity));

        $revisionEntity = new RevisionEntityEntity();
        $revisionEntity->setRevision($this->getRevision());
        $this->getRevision()->getRevisionEntities()->add($revisionEntity);
        $revisionEntity->setRevisionType($revisionType);
        if (method_exists($entity, '__toString'))
            $revisionEntity->setTitle((string)$entity);
        $this->addRevisionEntity($revisionEntity);

        $revisionEntitySetter = 'set' . $moduleOptions->getRevisionEntityFieldName();
        $workspaceEntity->$revisionEntitySetter($revisionEntity);

        // Re-exchange data after flush to map generated fields
        if ($revisionType ==  'INS' or $revisionType ==  'UPD') {
            $this->addReexchangeEntity(array(
                'workspaceEntity' => $workspaceEntity,
                'entity' => $entity,
                'revisionEntity' => $revisionEntity,
            ));
        } else {
            $revisionEntity->setWorkspaceEntity($workspaceEntity);
        }

        $workspaceEntities[] = $workspaceEntity;

        // Map many to many
        foreach ($this->getClassProperties($entity) as $key => $value) {

            if ($value instanceof PersistentCollection) {
                if (!$this->many2many) $this->many2many = array();
                $this->many2many[] = array(
                    'revisionEntity' => $revisionEntity,
                    'collection' => $value,
                );
            }
        }

        return $workspaceEntities;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entities = array();

        $this->buildRevision();

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions() AS $entity) {
            $entities = array_merge($entities, $this->workspaceEntity($entity, 'INS'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates() AS $entity) {
            $entities = array_merge($entities, $this->workspaceEntity($entity, 'UPD'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityDeletions() AS $entity) {
            $entities = array_merge($entities, $this->workspaceEntity($entity, 'DEL'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionDeletions() AS $collectionToDelete) {
            if ($collectionToDelete instanceof PersistentCollection) {
                $this->addCollection($collectionToDelete);
            }
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() AS $collectionToUpdate) {
            if ($collectionToUpdate instanceof PersistentCollection) {
                $this->addCollection($collectionToUpdate);
            }
        }

        $this->setEntities($entities);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->getEntities() and !$this->getInWorkspaceTransaction()) {
            $this->setInWorkspaceTransaction(true);

            $moduleOptions = \Workspace\Module::getModuleOptions();
            $entityManager = $moduleOptions->getEntityManager();
            $entityManager->beginTransaction();

            // Insert entites will trigger key generation and must be
            // re-exchanged (delete entites go out of scope)
            foreach ($this->getReexchangeEntities() as $entityMap) {
                $entityMap['workspaceEntity']->exchangeArray($this->getClassProperties($entityMap['entity']));
                $entityMap['revisionEntity']->setWorkspaceEntity($entityMap['workspaceEntity']);
            }

            // Flush revision and revisionEntities
            $entityManager->persist($this->getRevision());
            foreach ($this->getRevisionEntities() as $entity)
                $entityManager->persist($entity);
            $entityManager->flush();

            foreach ($this->getEntities() as $entity) {
                $entityManager->persist($entity);
            }

            // Persist many to many collections
            foreach ($this->getCollections() as $value) {
                $mapping = $value->getMapping();

                if (!$mapping['isOwningSide']) continue;

                $joinClassName = "Workspace\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                $moduleOptions->addJoinClass($joinClassName, $mapping);

                foreach ($this->many2many as $map) {
                    if ($map['collection'] == $value)
                        $revisionEntity = $map['revisionEntity'];
                }

                foreach ($value->getSnapshot() as $element) {
                    $workspace = new $joinClassName();

                    // Get current inverse revision entity
                    $revisionEntities = $entityManager->getRepository('Workspace\\Entity\\RevisionEntity')
                        ->findBy(array(
                            'targetEntityClass' => get_class($element),
                            'entityKeys' => serialize(array('id' => $element->getId())),
                        ), array('id' => 'DESC'), 1);

                    $inverseRevisionEntity = reset($revisionEntities);

                    $workspace->setTargetRevisionEntity($revisionEntity);
                    $workspace->setSourceRevisionEntity($inverseRevisionEntity);

                    $entityManager->persist($workspace);
                }
            }

            $entityManager->flush();

            $entityManager->commit();
            $this->resetEntities();
            $this->resetReexchangeEntities();
            $this->resetRevision();
            $this->resetRevisionEntities();
            $this->setInWorkspaceTransaction(false);
        }
    }
}
