<?php

namespace App\Repository;

use App\Entity\CmsUsers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CmsUsers|null find($id, $lockMode = null, $lockVersion = null)
 * @method CmsUsers|null findOneBy(array $criteria, array $orderBy = null)
 * @method CmsUsers[]    findAll()
 * @method CmsUsers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CmsUsersRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CmsUsers::class);
    }

//    /**
//     * @return CmsUsers[] Returns an array of CmsUsers objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CmsUsers
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
