<?php

namespace Claroline\CoreBundle\Library\Tools;

use Doctrine\ORM\EntityManager;
use Claroline\CoreBundle\Entity\Tool\DesktopTool;

/**
 * @DI\Service("claroline.tools.manager")
 */
class Manager
{
    private $em;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function addDesktopTools(User $user, array $tools, $autoflush = true)
    {
        $i = 1;

        foreach ($tools as $tool) {
            $udt = new DesktopTool();
            $udt->setUser($user);
            $udt->setOrder($i);
            $udt->setTool($tool);
            $i++;
            $this->em->persist($udt);
            
            if ($autoflush) {
                $this->em->flush();
            }
        }
    }
}
