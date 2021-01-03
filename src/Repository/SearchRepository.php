<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class SearchRepository extends EntityRepository
{
    public function findSeries($title)
    {
        return $this->createQueryBuilder('series')
            ->andWhere('series.title LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $title . '%')
            ->getQuery()
            ->execute();
    }
}
