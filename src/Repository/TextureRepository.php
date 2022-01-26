<?php

namespace App\Repository;

use App\Entity\Texture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Texture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Texture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Texture[]    findAll()
 * @method Texture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Texture::class);
    }

    // /**
    //  * @return Texture[] Returns an array of Texture objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Texture
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
