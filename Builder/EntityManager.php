<?php
namespace Intaro\NativeQueryBuilderBundle\Builder;

use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;
use Doctrine\Common\EventManager;

class EntityManager extends DoctrineEntityManager
{
    protected $cacheTime = 0;

    /**
     * Create a NativeQueryBuilder instance
     *
     * @return QueryBuilder
     */
    public function createNativeQueryBuilder()
    {
        return new NativeQueryBuilder($this, $this->cacheTime);
    }

    /**
     * {@inheritDoc}
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
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


    public function setCacheTime($cacheTime)
    {
        $this->cacheTime = $cacheTime;

        return $this;
    }

    public function getCacheTime()
    {
        return $this->cacheTime;
    }
}