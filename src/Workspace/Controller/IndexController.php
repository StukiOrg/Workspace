<?php

namespace Workspace\Controller;

use Zend\Mvc\Controller\AbstractActionController
 , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
 , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
 , Zend\Paginator\Paginator
 ;

class IndexController extends AbstractActionController
{
    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     */
    public function masterAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $userId = (int)$this->getEvent()->getRouteMatch()->getParam('userId');

        if (!$userId and \Workspace\Module::getModuleOptions()->getUser()) {
            $userId = \Workspace\Module::getModuleOptions()->getUser()->getId();
            if ($userId) {
                $user = \Workspace\Module::getModuleOptions()->getEntityManager()
                    ->getRepository(\Workspace\Module::getModuleOptions()->getUserEntityClassName())->find($userId);
            }
        }

        if (!isset($user))
            return $this->plugin('redirect')->toRoute('workspace/master');

        return array(
            'page' => $page,
            'user' => $user,
        );
    }

    /**
     * Show all revisions from all users
     */
    public function firehoseAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        return array(
            'page' => $page,
        );
    }


    /**
     * Renders a paginated list of revisions for the given user
     *
     * @param int $page
     */
    public function userAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $userId = (int)$this->getEvent()->getRouteMatch()->getParam('userId');

        $user = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository(\Workspace\Module::getModuleOptions()->getUserEntityClassName())->find($userId);

        return array(
            'userId' => $userId,
            'page' => $page,
            'user' => $user,
        );
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     *
     */
    public function revisionAction()
    {
        $revisionId = (int)$this->getEvent()->getRouteMatch()->getParam('revisionId');

        $revision = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\Revision')
            ->find($revisionId);

        if (!$revision)
            return $this->plugin('redirect')->toRoute('workspace');

        return array(
            'revision' => $revision,
        );
    }

    /**
     * Show the detail for a specific revision entity
     */
    public function revisionEntityAction()
    {
        $this->mapAllWorkspaceClasses();

        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $revisionEntityId = (int) $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');

        $revisionEntity = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('workspace');

        $repository = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity');

        return array(
            'page' => $page,
            'revisionEntity' => $revisionEntity,
            'workspaceService' => $this->getServiceLocator()->get('workspaceService'),
        );
    }

    /**
     * Lists revisions for the supplied entity.  Takes a workspace entity class or workspace class
     *
     * @param string $className
     * @param string $id
     */
    public function entityAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $entityClass = $this->getEvent()->getRouteMatch()->getParam('entityClass');

        return array(
            'entityClass' => $entityClass,
            'page' => $page,
        );
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param null|int $oldRev if null, pulled from the posted data
     * @param null|int $newRev if null, pulled from the posted data
     * @return Response
     */
    public function compareAction()
    {
        $revisionEntityId_old = $this->getRequest()->getPost()->get('revisionEntityId_old');
        $revisionEntityId_new = $this->getRequest()->getPost()->get('revisionEntityId_new');

        $revisionEntity_old = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId_old);
        $revisionEntity_new = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId_new);

        if (!$revisionEntity_old and !$revisionEntity_new)
            return $this->plugin('redirect')->toRoute('workspace');

        return array(
            'revisionEntity_old' => $revisionEntity_old,
            'revisionEntity_new' => $revisionEntity_new,
        );
    }

    public function oneToManyAction()
    {
        $moduleOptions = $this->getServiceLocator()
            ->get('workspaceModuleOptions');

        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $joinTable = $this->getEvent()->getRouteMatch()->getParam('joinTable');
        $revisionEntityId = $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');
        $mappedBy = $this->getEvent()->getRouteMatch()->getParam('mappedBy');

        $workspaceService = $this->getServiceLocator()->get('workspaceService');

        $revisionEntity = $moduleOptions->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('workspace');

        return array(
            'revisionEntity' => $revisionEntity,
            'page' => $page,
            'joinTable' => $joinTable,
            'mappedBy' => $mappedBy,
        );

    }

    public function associationSourceAction()
    {
        // When an association is requested all workspace metadata must
        // be loaded in order to create the necessary join table
        // information
        $moduleOptions = $this->getServiceLocator()
            ->get('workspaceModuleOptions');

        $this->mapAllWorkspaceClasses();

        $joinClasses = $moduleOptions->getJoinClasses();

        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $joinTable = $this->getEvent()->getRouteMatch()->getParam('joinTable');
        $revisionEntityId = $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');

        $workspaceService = $this->getServiceLocator()->get('workspaceService');

        $revisionEntity = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('workspace');

        return array(
            'revisionEntity' => $revisionEntity,
            'page' => $page,
            'joinTable' => $joinTable,
        );

    }

    public function associationTargetAction()
    {
        // When an association is requested all workspace metadata must
        // be loaded in order to create the necessary join table
        // information
        $moduleOptions = $this->getServiceLocator()
            ->get('workspaceModuleOptions');

        $this->mapAllWorkspaceClasses();

        foreach ($moduleOptions->getWorkspaceClassNames()
            as $className => $route) {
            $workspaceClassName = 'Workspace\\Entity\\' . str_replace('\\', '_', $className);
            $x = new $workspaceClassName;
        }
        $joinClasses = $moduleOptions->getJoinClasses();

        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $joinTable = $this->getEvent()->getRouteMatch()->getParam('joinTable');
        $revisionEntityId = $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');

        $workspaceService = $this->getServiceLocator()->get('workspaceService');

        $revisionEntity = \Workspace\Module::getModuleOptions()->getEntityManager()
            ->getRepository('Workspace\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('workspace');

        return array(
            'revisionEntity' => $revisionEntity,
            'page' => $page,
            'joinTable' => $joinTable,
        );

    }

    private function mapAllWorkspaceClasses()
    {
        // When an association is requested all workspace metadata must
        // be loaded in order to create the necessary join table
        // information
        $moduleOptions = $this->getServiceLocator()
            ->get('workspaceModuleOptions');

        foreach ($moduleOptions->getWorkspaceClassNames()
            as $className => $route) {
            $workspaceClassName = 'Workspace\\Entity\\' . str_replace('\\', '_', $className);
            $x = new $workspaceClassName;
        }
    }

}

