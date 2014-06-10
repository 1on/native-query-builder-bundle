<?php
namespace Intaro\NativeQueryBuilderBundle\Builder;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class NativeQueryBuilder extends EntityRepository
{
    const CACHE_TIME = 600;

    private $select = array();
    private $from = '';
    private $join = array();
    private $where = array();
    private $orderBy = array();
    private $limit = null;
    private $page = null;
    private $rest = '';

    private $queryParametes = array();
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Получение запроса NativeQuery со сформированным sql
     *
     * @param integer $cacheTime время кеширования запроса
     * @return NativeQuery
     */
    public function getQuery(ResultSetMapping $rsm, $cacheTime = self::CACHE_TIME)
    {
        $query = $this->em->createNativeQuery('', $rsm);

        $sql = 'SELECT ';
        foreach ($this->select as $field)
        {
            $sql .= $field;
            if ($field != end($this->select))
                $sql .= ',';
            $sql .= ' ';
        }
        $sql .= 'FROM ' . $this->from . ' ';
        foreach ($this->join as $table => $on)
            $sql .= $table . ' ON ' . $on . ' ';

        if (count($this->where) > 0)
        {
            $i = 1;
            $sql .= 'WHERE ' . $this->buildConditions($this->where);
        }

        if (count($this->orderBy) > 0)
        {
            $lastKey = key(array_slice($this->orderBy, -1, 1, true));
            $orderBy = ' ORDER BY ';
            foreach ($this->orderBy as $key => $value)
            {
                $orderBy .= $key . ' ' . $value;
                if ($key !== $lastKey)
                    $orderBy .= ', ';
            }
            $sql .= $orderBy;
        }
        $sql .= ' ' . $this->rest;


        if (!is_null($this->limit))
        {
            $sql .= ' LIMIT ?';
            $this->queryParametes[] = $this->limit;

            if (!is_null($this->page))
            {
                $sql .= ' OFFSET ?';
                $this->queryParametes[] = ($this->page - 1) * $this->limit;
            }
        }

        $parameterNumber = 1;
        foreach ($this->queryParametes as $parameter)
            $query->setParameter($parameterNumber++, $parameter);

        $query->setSql($sql);
        if ($cacheTime != 0)
            $query->useResultCache(true, $cacheTime);
        $this->resetQuery();

        return $query;
    }


    private function buildConditions(array $conditions)
    {
        $result = '';

        if (count($conditions) == 0)
            return $result;

        if (!is_array(reset($conditions)))
        {
            $result = $conditions[0];
            if (isset($conditions[1]))
                $this->queryParametes[] = $conditions[1];
            return $result;
        }

        $lastItem = end($conditions);
        if (isset($conditions['OR']) && count($conditions['OR'] > 0))
        {
            $lastItem = end($conditions['OR']);
            $result .= '(';
            foreach ($conditions['OR'] as $condition)
            {
                $result .= '(' . $this->buildConditions($condition) . ')';
                if ($condition != $lastItem)
                    $result .= ' OR ';
            }
            $result .= ') ';
        }

        foreach ($conditions as $key => $condition)
        {
            if ($key === 'OR')
                continue;

            $result .= $this->buildConditions($condition);
            if ($condition != $lastItem)
                $result .= ' AND ';
        }

        return $result;
    }


    protected function addSelect($select)
    {
        $this->select[] = $select;
    }

    protected function setFrom($from)
    {
        $this->from = $from;
    }

    protected function addJoin($table, $joinOn)
    {
        $this->join[$table] = $joinOn;
    }

    protected function addWhere($where, $parameter = null, $isOr = false)
    {
        if ($isOr)
        {
            if (!isset($this->where['OR']))
                $this->where['OR'] = array();
            $this->where['OR'][] = array($where, $parameter);
        }
        $this->where[] = array($where, $parameter);
    }

    protected function addOrderBy($field, $direction = 'DESC')
    {
        $this->orderBy[$field] = $direction;
    }


    protected function resetQuery()
    {
        $this->select = array();
        $this->from = '';
        $this->join = array();
        $this->where = array();
        $this->limit = null;
        $this->page = null;
        $this->orderBy = array();

        $this->queryParametes = array();
    }
}