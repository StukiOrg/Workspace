<?php

namespace Workspace\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return array();
    }

    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     */
    public function masterAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $userId = (int)$this->getEvent()->getRouteMatch()->getParam('userId');

        $user = null;
        if (!$userId and \Workspace\Module::getModuleOptions()->getUser()) {
            $userId = \Workspace\Module::getModuleOptions()->getUser()->getId();
            if ($userId) {
                $user = \Workspace\Module::getModuleOptions()->getEntityManager()
                    ->getRepository(\Workspace\Module::getModuleOptions()->getUserEntityClassName())->find($userId);
            }
        }

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
     * Submit a revision and all previous revisions
     *
     */
    public function revisionApproveSubmitAction()
    {
        $revisionId = (int)$this->getEvent()->getRouteMatch()->getParam('revisionId');

        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $revision = $em
            ->getRepository('Workspace\\Entity\\Revision')
            ->find($revisionId);

        if (!$revision)
            return $this->plugin('redirect')->toRoute('workspace');

        if ($this->zfcUserAuthentication()->getIdentity() !== $revision->getUser()) {
            throw new \BjyAuthorize\Exception\UnAuthorizedException('Only the owner may edit a revision comment.');
        }

        $builder = new AnnotationBuilder();
        $form = $builder->createForm($revision);
        $form->setData($revision->getArrayCopy());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData();
                $revision->setComment($form->getData()['comment']);
                $revision->setApprove('submitted');

                // Find all revisons not submitted
                $unapprovedRevisions = $em->getRepository('Workspace\\Entity\\Revision')->findBy(array(
                    'user' => $revision->getUser(),
                    'approve' => 'not submitted',
                ));

                // Find all revisons not submitted
                $rejectedRevisions = $em->getRepository('Workspace\\Entity\\Revision')->findBy(array(
                    'user' => $revision->getUser(),
                    'approve' => 'rejected',
                ));

                foreach ($unapprovedRevisions as $rev) {
                    if ($rev->getId() < $revision->getId()) {
                        $rev->setApprove('submitted');
                    }
                }
                foreach ($rejectedRevisions as $rev) {
                    if ($rev->getId() < $revision->getId()) {
                        $rev->setApprove('submitted');
                    }
                }

                $em->flush();

                die();
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setVariable('revision', $revision);
        $viewModel->setVariable('form', $form);
        return $viewModel;
    }



    /**
     * Approve a revision and all previous revisions to the master workspace
     *
     */
    public function approveAction()
    {
        $revisionId = (int)$this->getEvent()->getRouteMatch()->getParam('revisionId');

        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $revision = $em
            ->getRepository('Workspace\\Entity\\Revision')
            ->find($revisionId);

        if (!$revision) {
            return $this->plugin('redirect')->toRoute('workspace');
        }

        // Find all submitted revisons not submitted
        $unapprovedRevisions = $em->getRepository('Workspace\\Entity\\Revision')->findBy(array(
            'user' => $revision->getUser(),
            'approve' => 'submitted',
        ));
        foreach ($unapprovedRevisions as $index => $rev) {
            if ($rev->getId() >= $revision->getId()) {
                unset($unapprovedRevisions[$index]);
            }
        }

        // Find all rjected revisons not submitted
        $rejectedRevisions = $em->getRepository('Workspace\\Entity\\Revision')->findBy(array(
            'user' => $revision->getUser(),
            'approve' => 'rejected',
        ));
        foreach ($rejectedRevisions as $index => $rev) {
            if ($rev->getId() >= $revision->getId()) {
                unset($rejectedRevisions[$index]);
            }
        }

        $revisions = array_merge(array($revision), $unapprovedRevisions, $rejectedRevisions);

        usort($revisions, function($a, $b) {
            if ($a->getId() == $b->getId()) {
                throw new \Exception('Revision listed twice');
            }

            return ($a->getId() > $b->getId()) ? -1: 1;
        });

        $builder = new AnnotationBuilder();
        $form = $builder->createForm($revision);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData();
                foreach ($revisions as $rev) {
                    $rev->setApproveMessage($form->getData()['comment']);
                    $rev->setApprove('approved');
                    $rev->setApproveTimestamp(new \DateTime());
                    $rev->setApproveUser($this->zfcUserAuthentication()->getIdentity());
                }

                $em->flush();

                return $this->plugin('redirect')->toRoute('workspace/master');
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setVariable('revisions', $revisions);
        $viewModel->setVariable('form', $form);
        return $viewModel;
    }

    public function submittedAction()
    {
        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        // Find all submitted revisons by user
        $submittedRevisions = $em->getRepository('Workspace\\Entity\\Revision')->findBy(array(
            'approve' => 'submitted',
        ), array('id' => 'desc'));

        $users = array();
        $revisions = array();
        foreach ($submittedRevisions as $index => $rev) {
            if (!in_array($rev->getUser(), $users)) {
                $revisions[] = $rev;
                $users[] = $rev->getUser();
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setVariable('revisions', $revisions);
        return $viewModel;
    }


    /**
     * Allows a user to change a revision comment
     *
     * @param integer $rev
     *
     */
    public function revisionCommentEditAction()
    {
        $revisionId = (int)$this->getEvent()->getRouteMatch()->getParam('revisionId');

        $em = \Workspace\Module::getModuleOptions()->getEntityManager();

        $revision = $em
            ->getRepository('Workspace\\Entity\\Revision')
            ->find($revisionId);

        if (!$revision)
            return $this->plugin('redirect')->toRoute('workspace');

        if ($this->zfcUserAuthentication()->getIdentity() !== $revision->getUser()) {
            throw new \BjyAuthorize\Exception\UnAuthorizedException('Only the owner may edit a revision comment.');
        }

        $builder = new AnnotationBuilder();
        $form = $builder->createForm($revision);
        $form->setData($revision->getArrayCopy());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData();
                $revision->setComment($form->getData()['comment']);

                $em->persist($revision);
                $em->flush();

                die();
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setVariable('revision', $revision);
        $viewModel->setVariable('form', $form);
        return $viewModel;
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

