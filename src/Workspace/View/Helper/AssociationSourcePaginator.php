<?php

namespace Workspace\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use Workspace\Entity\AbstractWorkspace;

final class AssociationSourcePaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $revisionEntity, $joinTable)
    {
        $workspaceModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('workspaceModuleOptions');
        $entityManager = $workspaceModuleOptions->getEntityManager();
        $workspaceService = $this->getServiceLocator()->getServiceLocator()->get('workspaceService');

        foreach($workspaceService->getEntityAssociations($revisionEntity->getWorkspaceEntity()) as $field => $value) {
            if (isset($value['joinTable']['name']) and $value['joinTable']['name'] == $joinTable) {
                $mapping = $value;
                break;
            }
        }

        $repository = $entityManager->getRepository('Workspace\\Entity\\' . str_replace('\\', '_', $joinTable));

        $qb = $repository->createQueryBuilder('association');
        $qb->andWhere('association.sourceRevisionEntity = :var');
        $qb->setParameter('var', $revisionEntity);

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($workspaceModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}
