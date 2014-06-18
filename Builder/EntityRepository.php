<?php
namespace Intaro\NativeQueryBuilderBundle\Builder;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class EntityRepository extends BaseRepository
{
    public function getAll(array $order = array(), $cacheTime = null)
    {
        return $this->getBy(array(), $order, 1, null, $cacheTime);
    }

    public function getOneBy(array $parameters = array(), array $order = array(), $cacheTime = null)
    {
        $result = $this->getBy($parameters, $order, 1, 1, $cacheTime);
        if (isset($result[0]))
            return $result[0];
        else
            return null;
    }

    public function getBy(array $parameters = array(), array $order = array(), $page = 1, $limit = null, $cacheTime = null)
    {
        $builder = $this->getEntityManager()->createNativeQueryBuilder();

        $builder->select('entity.*')->from($this->_class->getTableName(), 'entity');

        foreach ($parameters as $key => $value)
        {
            if (is_null($value))
                $builder->where('entity.' . $key .' IS NULL');
            elseif (is_array($value))
                $builder->where('entity.' . $key .' IN (?)', $value);
            else
                $builder->where('entity.' . $key .' = ?', $value);
        }

        foreach ($order as $key => $value)
            $builder->orderBy('entity.' . $key, $value);

        $builder->limit($limit)->page($page);

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata($this->getEntityName(), 'entity');
        $result = $builder->getQuery($rsm, $cacheTime)->getResult();

        return $result;
    }
}
