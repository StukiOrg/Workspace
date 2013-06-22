<?php

namespace Workspace\Service;

use Zend\View\Helper\AbstractHelper;
use Workspace\Entity\AbstractWorkspace;
use Doctrine\Common\Collections\ArrayCollection;

class WorkspaceService extends AbstractHelper
{
    private $comment;

    static public function filterArray($entities)
    {
        $return = new ArrayCollection;

        foreach ($entities as $entity) {
            if ($entity = self::filter($entity)) {
                $return->add($entity);
            }
        }

        return $return;
    }

    // Return the current workspace entity for the given entity
    static public function filter($entity)
    {
        if ($entity instanceof ArrayCollection or is_array($entity)) {
            return self::filterArray($entity);
        }

        $moduleOptions = \Workspace\Module::getModuleOptions();

        $found = false;
        foreach (array_keys($moduleOptions->getWorkspaceClassNames()) as $workspaceEntityClass) {
            if ($entity instanceof $workspaceEntityClass) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return $entity;
        }

        $workspaceService = $moduleOptions->getWorkspaceService();
        $workspaceRevisionEntity = $workspaceService->workspaceRevisionEntity($entity);
        if (!$workspaceRevisionEntity) {
            return false;
        }

        $entity->exchangeArray($workspaceRevisionEntity->getWorkspaceEntity()->getArrayCopy());

        return $entity;
    }

    /**
     * A utility function to get an association entity from an audited
     * association identifier value in getArrayCopy
     */
    static public function getAssociation($className, $identifier) {
        $moduleOptions = \Workspace\Module::getModuleOptions();
        if (!$moduleOptions or !$identifier) return null;
        $entityManager = $moduleOptions->getEntityManager();

        return $entityManager->getRepository($className)->find($identifier);
    }


    /**
     * To add a comment to a revision fetch this object before flushing
     * and set the comment.  The comment will be fetched by the revision
     * and reset after reading
     */
    public function getComment()
    {
        $comment = $this->comment;
        $this->comment = null;

        return $comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getEntityValues($entity) {
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $metadata = $em->getClassMetadata(get_class($entity));
        $fields = $metadata->getFieldNames();

#        $targetEntityMetadata = $em->getClassMetadata($entity->getRevisionEntity()->getTargetEntity());

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        ksort($return);

        return $return;
    }


    public function getEntityAssociations(AbstractWorkspace $entity)
    {
        $associations = array();
        foreach ($entity->getAssociationMappings() as $mapping) {
            $associations[$mapping['fieldName']] = $mapping;
        }

        return $associations;
    }

    /**
     * Find a mapping to the given field for 1:many
     */
    public function getAssociationRevisionEntity(AbstractWorkspace $entity, $field, $value) {
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        foreach ($entity->getAssociationMappings() as $mapping) {

            if ($mapping['fieldName'] == $field) {
                $qb = $em->createQueryBuilder();
                $qb->select('revisionEntity')
                    ->from('Workspace\\Entity\\RevisionEntity', 'revisionEntity')
                    ->innerJoin('revisionEntity.revision', 'revision')
                    ->andWhere('revisionEntity.targetEntityClass = ?1')
                    ->andWhere('revisionEntity.entityKeys = ?2')
                    ->andWhere('revision.timestamp <= ?3')
                    ->setParameter(1, $mapping['targetEntity'])
                    ->setParameter(2, serialize(array('id' => $value)))
                    ->setParameter(3, $entity->getRevisionEntity()->getRevision()->getTimestamp())
                    ->orderBy('revision.timestamp', 'DESC')
                    ->setMaxResults(1);

                $result = $qb->getQuery()->getResult();

                if ($result) return reset($result);
            }

        }

        return;
    }

    public function getEntityIdentifierValues($entity, $cleanRevisionEntity = false)
    {
        $entityManager = \Workspace\Module::getModuleOptions()->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        // Get entity metadata - Workspace entities will always have composite keys
        $metadata = $metadataFactory->getMetadataFor(get_class($entity));
        $values = $metadata->getIdentifierValues($entity);

        if ($cleanRevisionEntity and $values['revisionEntity'] instanceof \Workspace\Entity\RevisionEntity) {
            unset($values['revisionEntity']);
        }

        foreach ($values as $key => $val) {
            if (gettype($val) == 'object') $values[$key] = $val->getId();
        }

        return $values;
    }

    /**
     * Pass an workspaceed entity or the workspace entity
     * and return a collection of RevisionEntity s
     * for that record
     */
    public function getRevisionEntities($entity)
    {
        $entityManager = \Workspace\Module::getModuleOptions()->getEntityManager();

        if (gettype($entity) != 'string' and in_array(get_class($entity), array_keys(\Workspace\Module::getModuleOptions()->getWorkspaceClassNames()))) {
            $workspaceEntityClass = 'Workspace\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $this->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractWorkspace) {
            $workspaceEntityClass = get_class($entity);
            $identifiers = $this->getEntityIdentifierValues($entity, true);
        } else {
            $workspaceEntityClass = 'Workspace\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('workspaceEntityClass' => $workspaceEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        return $entityManager->getRepository('Workspace\\Entity\\RevisionEntity')
            ->findBy($search, array('id' => 'DESC'));
    }

    public function workspaceRevisionEntity($entity)
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();

        $entityManager = $moduleOptions->getEntityManager();
        $workspaceService = $moduleOptions->getWorkspaceService();

        $user = $moduleOptions->getUser();

        $revisionEntities = $entityManager->getRepository('Workspace\\Entity\\RevisionEntity')->findBy(array(
            'targetEntityClass' => get_class($entity),
            'entityKeys' => serialize($workspaceService->getEntityIdentifierValues($entity)),
        ), array('id' => 'DESC'));

        foreach ($revisionEntities as $revisionEntity) {
            if ($revisionEntity->getRevision()->getUser() == $user) {
                return $revisionEntity;
            }

            if ($revisionEntity->getRevision()->getApprove() == 'approved') {
                return $revisionEntity;
            }
        }
    }

    public function getUser()
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();
        return $moduleOptions->getUser();
    }
}
