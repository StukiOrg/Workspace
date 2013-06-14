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

final class RevisionEntityPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $entity)
    {
        $workspaceModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('workspaceModuleOptions');
        $entityManager = $workspaceModuleOptions->getEntityManager();
        $workspaceService = $this->getServiceLocator()->getServiceLocator()->get('workspaceService');

        if (gettype($entity) != 'string' and in_array(get_class($entity), array_keys($workspaceModuleOptions->getWorkspaceClassNames()))) {
            $workspaceEntityClass = 'Workspace\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $workspaceService->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractWorkspace) {
            $workspaceEntityClass = get_class($entity);
            $identifiers = $workspaceService->getEntityIdentifierValues($entity, true);
        } else {
            $workspaceEntityClass = 'Workspace\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('workspaceEntityClass' => $workspaceEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        $queryBuilder = $entityManager->getRepository('Workspace\\Entity\\RevisionEntity')->createQueryBuilder('rev');
        $queryBuilder->orderBy('rev.id', 'DESC');
        $i = 0;
        foreach ($search as $key => $val) {
            $i ++;
            $queryBuilder->andWhere("rev.$key = ?$i");
            $queryBuilder->setParameter($i, $val);
        }

        $adapter = new DoctrineAdapter(new ORMPaginator($queryBuilder));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($workspaceModuleOptions->getPaginatorLimit());
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}