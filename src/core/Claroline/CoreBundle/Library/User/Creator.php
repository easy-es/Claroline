<?php
namespace Claroline\CoreBundle\Library\User;

use Doctrine\ORM\EntityManager;
use Claroline\CoreBundle\Library\Workspace\Creator as WsCreator;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Event\LogUserCreateEvent;
use Claroline\CoreBundle\Library\Tools\Manager as ToolManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.user.creator")
 */
class Creator
{
    const BATCH_SIZE = 150;

    private $em;
    private $wsCreator;
    private $ed;
    private $sc;
    private $toolManager;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "wsCreator" = @DI\Inject("claroline.workspace.creator"),
     *     "ed" = @DI\Inject("event_dispatcher"),
     *     "sc" = @DI\Inject("security.context"),
     *     "toolManager" = @DI\Inject("claroline.tools.manager)
     * })
     */
    public function __construct(
        EntityManager $em,
        WsCreator $wsCreator,
        EventDispatcher $ed,
        SecurityContext $sc,
        ToolManager $toolManager
    )
    {
        $this->em = $em;
        $this->wsCreator = $wsCreator;
        $this->ed = $ed;
        $this->sc = $sc;
        $this->toolManager = $toolManager;
    }

    /**
     * Creates a user. This method will create the user personal workspace
     * and persist the $user.
     *
     * @param User $user
     *
     * @return User
     */
    public function create(User $user, $autoflush = true)
    {
        $user->addRole($this->em->getRepository('ClarolineCoreBundle:Role')->findOneByName('ROLE_USER'));
        $this->em->persist($user);
        $this->wsCreator->setPersonalWorkspace($user);
        $requiredTools = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool')->findDesktopDefaultTools();
        $this->toolManager->addRequiredTools($user, $requiredTools);

        if ($autoflush) {
            $this->em->flush();
        }

        $log = new LogUserCreateEvent($user);
        $this->ed->dispatch('log', $log);

        return $user;
    }

    /**
     * Expects an array of users. Each user must be defined this way:
     * array(
     *     [0] => 'firstname',
     *     [1] => 'lastname',
     *     [2] => 'username',
     *     [3] => 'password',
     *     [4] => 'code',
     *     [5] => 'mail' (optionnal)
     * )
     * @param array $users
     */
    public function import($users)
    {
        $role = $this->em->getRepository('ClarolineCoreBundle:Role')->findOneBy(array('name' => 'ROLE_USER'));
        $requiredTools = $this->em->getRepository('ClarolineCoreBundle:Tool\Tool')->findDesktopDefaultTools();
        $i = $j = 0;

        foreach ($users as $user) {
            $userEntity = new User();
            $userEntity->addRole($role);
            $userEntity->setFirstName($user[0]);
            $userEntity->setLastName($user[1]);
            $userEntity->setUsername($user[2]);
            $userEntity->setPlainPassword($user[3]);
            $userEntity->setAdministrativeCode($user[4]);

            if (isset($user[5])) {
                $userEntity->setMail($user[5]);
            }

            $this->toolManager->addRequiredTools($userEntity, $requiredTools);
            $this->em->persist($userEntity);
            $log = new LogUserCreateEvent($userEntity);
            $this->ed->dispatch('log', $log);

            if (($i % self::BATCH_SIZE) === 0) {
                $j++;

                $this->em->flush();
                $this->em->clear();

                echo ("batch [{$j}] | users [{$i}] | UOW  [{$this->em->getUnitOfWork()->size()}]".PHP_EOL);

                $role = $this->em->getRepository('ClarolineCoreBundle:Role')->findOneBy(array('name' => 'ROLE_USER'));
                $requiredTools = $this->findRequiredTools();
                $doer = $this->em->getRepository('ClarolineCoreBundle:User')
                    ->findOneByUsername($this->sc->getToken()->getUser()->getUsername());
                $this->em->merge($doer);
                $this->sc->getToken()->setUser($doer);
            }

            $i++;
        }

        $this->em->flush();
        $this->em->clear();
    }
}
