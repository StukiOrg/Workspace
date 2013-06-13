<?php

namespace Workspace\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    , Zend\ServiceManager\ServiceLocatorInterface
    , Zend\View\Model\ViewModel
    , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
    , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
    , Zend\Paginator\Paginator
    , Workspace\Entity\AbstractWorkspace
    ;

final class RevisionPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $filter = array())
    {
        $workspaceModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('workspaceModuleOptions');
        $entityManager = $workspaceModuleOptions->getEntityManager();

        $repository = $entityManager->getRepository('Workspace\\Entity\\Revision');

        $qb = $repository->createQueryBuilder('revision');
        $qb->orderBy('revision.id', 'DESC');

        $i = 0;
        foreach($filter as $field => $value) {
            if (!is_null($value)) {
                $qb->andWhere("revision.$field = ?$i");
                $qb->setParameter($i, $value);
            } else {
                $qb->andWhere("revision.$field is NULL");
            }
        }

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($workspaceModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}
