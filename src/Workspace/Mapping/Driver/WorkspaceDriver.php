<?php

namespace Workspace\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

final class WorkspaceDriver implements MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     */
    function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();
        $entityManager = $moduleOptions->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();
        $builder = new ClassMetadataBuilder($metadata);

        if ($className == 'Workspace\\Entity\RevisionEntity') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addManyToOne('revision', 'Workspace\\Entity\\Revision', 'revisionEntities');
            $builder->addField('entityKeys', 'string');
            $builder->addField('workspaceEntityClass', 'string');
            $builder->addField('targetEntityClass', 'string');
            $builder->addField('revisionType', 'string');
            $builder->addField('title', 'string', array('nullable' => true));

            $metadata->setTableName($moduleOptions->getRevisionEntityTableName());
            return;
        }

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'Workspace\\Entity\\Revision') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text', array('nullable' => true));
            $builder->addField('timestamp', 'datetime');
            $builder->addField('approve', 'string');
            $builder->addField('approveMessage', 'text', array('nullable' => true));
            $builder->addField('approveTimestamp', 'datetime', array('nullable' => true));

            // Add association between RevisionEntity and Revision
            $builder->addOneToMany('revisionEntities', 'Workspace\\Entity\\RevisionEntity', 'revision');

            // Add assoication between User and Revision
            $userMetadata = $metadataFactory->getMetadataFor($moduleOptions->getUserEntityClassName());
            $builder
                ->createManyToOne('user', $userMetadata->getName())
                ->addJoinColumn('user_id', $userMetadata->getSingleIdentifierColumnName())
                ->build();

            // Add approving user
            $builder
                ->createManyToOne('approveUser', $userMetadata->getName())
                ->addJoinColumn('approve_user_id', $userMetadata->getSingleIdentifierColumnName())
                ->build();

            $metadata->setTableName($moduleOptions->getRevisionTableName());
            return;
        }

#        $builder->createField('workspace_id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $identifiers = array();
#        $metadata->setIdentifier(array('workspace_id'));

        //  Build a discovered many to many join class
        $joinClasses = $moduleOptions->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {

            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();

            $builder->addManyToOne('targetRevisionEntity', 'Workspace\\Entity\\RevisionEntity');
            $builder->addManyToOne('sourceRevisionEntity', 'Workspace\\Entity\\RevisionEntity');

            $metadata->setTableName($moduleOptions->getTableNamePrefix() . $joinClasses[$className]['joinTable']['name'] . $moduleOptions->getTableNameSuffix());
//            $metadata->setIdentifier($identifiers);
            return;
        }


        // Get the entity this entity workspaces
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        $workspaceClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getWorkspaceEntityClass());

        $builder->addManyToOne($moduleOptions->getRevisionEntityFieldName(), 'Workspace\\Entity\\RevisionEntity');
# Compound keys removed in favor of simple id
        $identifiers[] = $moduleOptions->getRevisionEntityFieldName();

        // Add fields from target to workspace entity
        foreach ($workspaceClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $workspaceClassMetadata->getTypeOfField($fieldName), array('nullable' => true));
            if ($workspaceClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
        }

        foreach ($workspaceClassMetadata->getAssociationMappings() as $mapping) {
            if (!$mapping['isOwningSide']) continue;

            if (isset($mapping['joinTable'])) {
                continue;
            }

            if (isset($mapping['joinTableColumns'])) {
                foreach ($mapping['joinTableColumns'] as $field) {
                    $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                }
            } elseif (isset($mapping['joinColumnFieldNames'])) {
                foreach ($mapping['joinColumnFieldNames'] as $field) {
                    $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                }
            } else {
                throw new \Exception('Unhandled association mapping');
            }

        }

        $metadata->setTableName($moduleOptions->getTableNamePrefix() . $workspaceClassMetadata->getTableName() . $moduleOptions->getTableNameSuffix());
        $metadata->setIdentifier($identifiers);

        return;
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    function getAllClassNames()
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();
        $entityManager = $moduleOptions->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        $workspaceEntities = array();
        foreach ($moduleOptions->getWorkspaceClassNames() as $name => $targetClassOptions) {
            $workspaceClassName = "Workspace\\Entity\\" . str_replace('\\', '_', $name);
            $workspaceEntities[] = $workspaceClassName;
            $workspaceClassMetadata = $metadataFactory->getMetadataFor($name);

            // FIXME:  done in autoloader
            foreach ($workspaceClassMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable']['name'])) {
                    $workspaceJoinTableClassName = "Workspace\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                    $workspaceEntities[] = $workspaceJoinTableClassName;
                    $moduleOptions->addJoinClass($workspaceJoinTableClassName, $mapping);
                }
            }
        }

        // Add revision (manage here rather than separate namespace)
        $workspaceEntities[] = 'Workspace\\Entity\\Revision';
        $workspaceEntities[] = 'Workspace\\Entity\\RevisionEntity';

        return $workspaceEntities;
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    function isTransient($className) {
        return true;
    }
}
