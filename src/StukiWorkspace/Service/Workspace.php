<?php

namespace Workspace\Service;

class Workspace {
    static public function __invoke($entity)
    {
        $moduleOptions = \Workspace\Module::getModuleOptions();

        $found = false;
        foreach (array_keys($moduleOptions->getAuditedClassNames() as $workspaceEntityClass) {
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

        $entity->exchangeArray($workspaceRevisionEntity->getAuditEntity()->getArrayCopy());

        return $entity;
    }
}