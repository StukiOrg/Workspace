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

final class EntityOptions extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($entityClass = null)
    {
        $workspaceModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('workspaceModuleOptions');
        $workspaceedClassNames = $workspaceModuleOptions->getWorkspaceedClassNames();

        if ($entityClass) return $workspaceedClassNames[$entityClass];

        return $workspaceedClassNames;
    }
}
