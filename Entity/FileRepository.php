<?php

namespace SmartCore\Bundle\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    /**
     * @param Collection|int $collection
     *
     * @return int
     */
    public function count($collection)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('count(e.id)')
            ->where('e.collection = :collection')
            ->setParameter('collection', $collection)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Collection|int $collection
     *
     * @return int
     */
    public function summarySize($collection)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('sum(e.size)')
            ->where('e.collection = :collection')
            ->setParameter('collection', $collection)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }
}
