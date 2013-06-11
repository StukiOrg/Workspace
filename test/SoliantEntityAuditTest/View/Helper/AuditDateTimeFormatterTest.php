<?php

namespace WorkspaceTest\View\Helper;

use WorkspaceTest\Bootstrap
    ;

class WorkspaceDateTimeFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testFormatter()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('WorkspaceDateTimeFormatter');

        $now = new \DateTime();
        $helper->setDateTimeFormat('U');
        $this->assertEquals($helper($now), $now->format('U'));
    }
}
