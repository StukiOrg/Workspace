<?php

namespace WorkspaceTest\View\Helper;

use WorkspaceTest\Bootstrap
    , WorkspaceTest\Models\Bootstrap\Album
    ;

class EntityOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testRevisionsAreReturnedInPaginator()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('workspaceEntityOptions');

        $helper('WorkspaceTest\Models\Bootstrap\Song');
        $helper();
    }
}
