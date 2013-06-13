<?php

namespace Claroline\CoreBundle\Repository;

use Claroline\CoreBundle\Library\Testing\FixtureTestCase;

class ToolRepositoryTest extends FixtureTestCase
{
    private $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool');
    }

    public function testFindDesktopDefaultTools()
    {
        $this->assertEquals(4, count($this->repo->findDesktopDefaultTools()));
    }
}