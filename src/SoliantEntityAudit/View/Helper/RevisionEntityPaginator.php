<?php

namespace StukiWorkspace\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    , Zend\ServiceManager\ServiceLocatorInterface
    , Zend\View\Model\ViewModel
    , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
    , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
    , Zend\Paginator\Paginator
    , StukiWorkspace\Entity\AbstractAudit
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
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');
        $entityManager = $auditModuleOptions->getEntityManager();
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');

        if (gettype($entity) != 'string' and in_array(get_class($entity), array_keys($auditModuleOptions->getAuditedClassNames()))) {
            $auditEntityClass = 'StukiWorkspace\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $auditService->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractAudit) {
            $auditEntityClass = get_class($entity);
            $identifiers = $auditService->getEntityIdentifierValues($entity, true);
        } else {
            $auditEntityClass = 'StukiWorkspace\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('auditEntityClass' => $auditEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        $queryBuilder = $entityManager->getRepository('StukiWorkspace\\Entity\\RevisionEntity')->createQueryBuilder('rev');
        $queryBuilder->orderBy('rev.id', 'DESC');
        $i = 0;
        foreach ($search as $key => $val) {
            $i ++;
            $queryBuilder->andWhere("rev.$key = ?$i");
            $queryBuilder->setParameter($i, $val);
        }

        $adapter = new DoctrineAdapter(new ORMPaginator($queryBuilder));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}