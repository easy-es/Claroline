<?php

namespace Claroline\CoreBundle\Library\Message;

use Claroline\CoreBundle\Entity\Message;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\UserMessage;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.message.writer")
 */
class Writer
{
    private $em;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function create(User $sender, $receiverString, array $receivers, $content, $object, Message $parent = null)
    {
        $message = new Message();
        $message->setUser($sender);
        $message->setSenderUsername($sender->getUsername());
        $message->setParent($parent);
        $message->setReceiverUsername($receiverString);
        $message->setContent($content);
        $message->setObject($object);
        $this->em->persist($message);

        $userMessage = new UserMessage(true);
        $userMessage->setUser($sender);
        $userMessage->setMessage($message);
        $this->em->persist($userMessage);

        foreach ($receivers as $receiver) {
            $userMessage = new UserMessage();
            $userMessage->setUser($receiver);
            $userMessage->setMessage($message);
            $this->em->persist($userMessage);
            $this->em->persist($message);
        }

        $this->em->flush();

        return $message;
    }

    public function markAsRead(UserMessage $userMessage)
    {
        $userMessage->markAsRead();
        $this->em->persist($userMessage);
        $this->em->flush();
    }

    public function markAsRemoved(UserMessage $userMessage)
    {
        $userMessage->markAsRemoved();
        $this->em->persist($userMessage);
        $this->em->flush();
    }

    public function markAsUnremoved(UserMessage $userMessage)
    {
        $userMessage->markAsUnremoved();
        $this->em->persist($userMessage);
        $this->em->flush();
    }
}