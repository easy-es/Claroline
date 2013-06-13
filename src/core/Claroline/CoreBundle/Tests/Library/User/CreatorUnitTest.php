<?php

namespace Claroline\CoreBundle\Library\User;

use Claroline\CoreBundle\Library\Testing\MockeryTestCase;
use \Mockery as m;

class CreatorUnitTest extends MockeryTestCase
{
    private $em;
    private $wsCreator;
    private $ed;
    private $sc;

    protected function setUp()
    {
        parent::setUp();
        $this->em = m::mock('Doctrine\ORM\EntityManager');
        $this->wsCreator = m::mock('Claroline\CoreBundle\Library\Workspace\Creator');
        $this->ed = m::mock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->sc = m::mock('Symfony\Component\Security\Core\SecurityContext');
        $this->creator = new Creator($this->em, $this->wsCreator, $this->ed, $this->sc);
    }

    public function testCreateUserGivesUserRole()
    {
        $user = m::mock('Claroline\CoreBundle\Entity\User');
        $role = m::mock('Claroline\CoreBundle\Entity\Role');
        $repo = m::mock('Claroline\CoreBundle\Repository\RoleRepository');
        m::getConfiguration()->allowMockingNonExistentMethods(true);
        $repo->shouldReceive('findOneByName')->with('ROLE_USER')->andReturn($role);
        m::getConfiguration()->allowMockingNonExistentMethods(false);
        $this->em->shouldReceive('getRepository')->with('ClarolineCoreBundle:Role')->andReturn($repo);
        $this->wsCreator->shouldReceive('setPersonalWorkspace')->with($user);
        //$this->creator->create($user);
        //$this->assertEquals(1, count($user->getRoles()));
    }
}