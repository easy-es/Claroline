<?php

namespace Claroline\CoreBundle\Library\Doctrine\ORM;

use Doctrine\ORM\EntityManager as m;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;

class EntityManager extends m
{
    private $isFlushActivated;

    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $this->isFlushActivated = true;
        parent::__construct($conn, $config, $eventManager);
    }

    public static function create($conn, Configuration $config, EventManager $eventManager = null) {
        if ( ! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = \Doctrine\DBAL\DriverManager::getConnection(
                    $conn, $config, ($eventManager ?: new EventManager())
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                     throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new EntityManager($conn, $config, $conn->getEventManager());
    }

    public function enableFlush()
    {
        $this->isFlushActivated = true;
    }

    public function disableFlush()
    {
        $this->isFlushActivated = false;
    }

    public function flush($entity = null)
    {
        if ($this->isFlushActivated) {
            parent::flush($entity);
        }
    }
}
