<?php

namespace StukiWorkspaceTest\View\Helper;

use StukiWorkspaceTest\Bootstrap
    ;

class StukiDateTimeFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testFormatter()
    {
        $sm = Bootstrap::getApplication()->getServiceManager();
        $helper = $sm->get('viewhelpermanager')->get('stukiDateTimeFormatter');

        $now = new \DateTime();
        $helper->setDateTimeFormat('U');
        $this->assertEquals($helper($now), $now->format('U'));
    }
}
