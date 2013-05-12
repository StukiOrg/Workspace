<?php

namespace StukiWorkspaceTest\View\Helper;

use StukiWorkspaceTest\Bootstrap
    , StukiWorkspaceTest\Models\Bootstrap\Album
    ;

class EntityOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testRevisionsAreReturnedInPaginator()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('stukiEntityOptions');

        $helper('StukiWorkspaceTest\Models\Bootstrap\Song');
        $helper();
    }
}
