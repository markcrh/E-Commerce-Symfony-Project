<?php

namespace App\Repository;

use App\Entity\UserMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMovie>
 *
 * @method UserMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMovie[]    findAll()
 * @method UserMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMovie::class);
    }

//    /**
//     * @return UserMovie[] Returns an array of UserMovie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserMovie
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
