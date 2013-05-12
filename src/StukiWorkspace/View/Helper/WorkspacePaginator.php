<?php

namespace StukiWorkspace\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use StukiWorkspace\Entity\AbstractAudit;

final class WorkspacePaginator extends AbstractHelper implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function __invoke($page, $user, $filter = array())
    {
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');
        $entityManager = $auditModuleOptions->getEntityManager();
#        $stukiWorkspaceService = $this->getServiceLocator()->getServiceLocator()->get('stukiWorkspaceService');

        $repository = $entityManager->getRepository('StukiWorkspace\\Entity\\Revision');

        $qb = $repository->createQueryBuilder('revision');

        if ($user) {
            $qb->orWhere("revision.user = :user");
            $qb->setParameter('user', $user);
            $qb->orWhere("revision.approve = 'approved'");
#            die('set alert');
        } else {
            $qb->andWhere("revision.user IS NULL");
        }
        $qb->orderBy('revision.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}
