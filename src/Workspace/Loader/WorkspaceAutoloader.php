<?php

namespace Workspace\Loader;

use Zend\Loader\StandardAutoloader
    , Zend\ServiceManager\ServiceManager
    , Zend\Code\Reflection\ClassReflection
    , Zend\Code\Generator\ClassGenerator
    , Zend\Code\Generator\MethodGenerator
    , Zend\Code\Generator\PropertyGenerator
    ;

class WorkspaceAutoloader extends StandardAutoloader
{
    /**
     * Dynamically scope an workspace class
     *
     * @param  string $className
     * @return false|string
     */
    public function loadClass($className, $type)
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();
        if (!$moduleOptions) return;
        $entityManager = $moduleOptions->getEntityManager();

        $workspaceClass = new ClassGenerator();

        //  Build a discovered many to many join class
        $joinClasses = $moduleOptions->getJoinClasses();

        if (in_array($className, array_keys($joinClasses))) {

            $workspaceClass->setNamespaceName("Workspace\\Entity");
            $workspaceClass->setName($className);
            $workspaceClass->setExtendedClass('AbstractWorkspace');

            $workspaceClass->addProperty('id', null, PropertyGenerator::FLAG_PROTECTED);

            $workspaceClass->addProperty('targetRevisionEntity', null, PropertyGenerator::FLAG_PROTECTED);
            $workspaceClass->addProperty('sourceRevisionEntity', null, PropertyGenerator::FLAG_PROTECTED);

            $workspaceClass->addMethod(
                'getTargetRevisionEntity', array(),
                MethodGenerator::FLAG_PUBLIC,
                'return $this->targetRevisionEntity;'
            );

            $workspaceClass->addMethod(
                'getSourceRevisionEntity', array(),
                MethodGenerator::FLAG_PUBLIC,
                'return $this->sourceRevisionEntity;'
            );

            $workspaceClass->addMethod(
                'getId', array(),
                MethodGenerator::FLAG_PUBLIC,
                'return $this->id;'
            );

            $workspaceClass->addMethod(
                'setTargetRevisionEntity', array('value'),
                MethodGenerator::FLAG_PUBLIC,
                '$this->targetRevisionEntity = $value;' . "\n" .
                    'return $this;'
            );

            $workspaceClass->addMethod(
                'setSourceRevisionEntity', array('value'),
                MethodGenerator::FLAG_PUBLIC,
                '$this->sourceRevisionEntity = $value;' . "\n" .
                    'return $this;'
            );

#            print_r($workspaceClass->generate());
#            die();
            eval($workspaceClass->generate());
            return;
        }

        // Add revision reference getter and setter
        $workspaceClass->addProperty($moduleOptions->getRevisionEntityFieldName(), null, PropertyGenerator::FLAG_PROTECTED);
        $workspaceClass->addMethod(
            'get' . $moduleOptions->getRevisionEntityFieldName(),
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return \$this->" .  $moduleOptions->getRevisionEntityFieldName() . ";");

        $workspaceClass->addMethod(
            'set' . $moduleOptions->getRevisionEntityFieldName(),
            array('value'),
            MethodGenerator::FLAG_PUBLIC,
            " \$this->" .  $moduleOptions->getRevisionEntityFieldName() . " = \$value;\nreturn \$this;
            ");


        // Verify this autoloader is used for target class
        #FIXME:  why is this sent work outside the set namespace?
        foreach($moduleOptions->getWorkspaceClassNames() as $targetClass => $targetClassOptions) {

             $workspaceClassName = 'Workspace\\Entity\\' . str_replace('\\', '_', $targetClass);

             if ($workspaceClassName == $className) {
                 $currentClass = $targetClass;
             }
             $autoloadClasses[] = $workspaceClassName;
        }
        if (!in_array($className, $autoloadClasses)) return;

        // Get fields from target entity
        $metadataFactory = $entityManager->getMetadataFactory();

        $workspaceedClassMetadata = $metadataFactory->getMetadataFor($currentClass);
        $fields = $workspaceedClassMetadata->getFieldNames();
        $identifiers = $workspaceedClassMetadata->getFieldNames();

        $service = \Workspace\Module::getModuleOptions()->getWorkspaceService();

        // Generate workspace entity
        foreach ($fields as $field) {
            $workspaceClass->addProperty($field, null, PropertyGenerator::FLAG_PROTECTED);
        }

        foreach ($workspaceedClassMetadata->getAssociationNames() as $associationName) {
            $workspaceClass->addProperty($associationName, null, PropertyGenerator::FLAG_PROTECTED);
            $fields[] = $associationName;
        }


        $workspaceClass->addMethod(
            'getAssociationMappings',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            "return unserialize('" . serialize($workspaceedClassMetadata->getAssociationMappings()) . "');"
        );

        // Add exchange array method
        $setters = array();
        foreach ($fields as $fieldName) {
            $setters[] = '$this->' . $fieldName . ' = (isset($data["' . $fieldName . '"])) ? $data["' . $fieldName . '"]: null;';
            $arrayCopy[] = "    \"$fieldName\"" . ' => $this->' . $fieldName;
        }

        $workspaceClass->addMethod(
            'getArrayCopy',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            "return array(\n" . implode(",\n", $arrayCopy) . "\n);"
        );

        $workspaceClass->addMethod(
            'exchangeArray',
            array('data'),
            MethodGenerator::FLAG_PUBLIC,
            implode("\n", $setters)
        );

        // Add function to return the entity class this entity workspaces
        $workspaceClass->addMethod(
            'getWorkspaceEntityClass',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return '" .  addslashes($currentClass) . "';"
        );

        $workspaceClass->setNamespaceName("Workspace\\Entity");
        $workspaceClass->setName(str_replace('\\', '_', $currentClass));
        $workspaceClass->setExtendedClass('AbstractWorkspace');

        #    $workspaceedClassMetadata = $metadataFactory->getMetadataFor($currentClass);
        $workspaceedClassMetadata = $metadataFactory->getMetadataFor($currentClass);

            foreach ($workspaceedClassMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable']['name'])) {
                    $workspaceJoinTableClassName = "Workspace\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                    $workspaceEntities[] = $workspaceJoinTableClassName;
                    $moduleOptions->addJoinClass($workspaceJoinTableClassName, $mapping);
                }
            }

#        if ($workspaceClass->getName() == 'AppleConnect_Entity_UserAuthenticationLog') {
#            echo '<pre>';
#            echo($workspaceClass->generate());
#            die();
#        }

        eval($workspaceClass->generate());

#            die();

        return true;
    }

}
