<?php

namespace App\Repository;

use App\Entity\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Model|null find($id, $lockMode = null, $lockVersion = null)
 * @method Model|null findOneBy(array $criteria, array $orderBy = null)
 * @method Model[]    findAll()
 * @method Model[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Model::class);
    }

    public function findAllAndPaginate($index, $pageSize, $category): array
    {
        $query = $this->createQueryBuilder('m');
            if($category != null){
                $query->where('m.category = :cat')
                    ->setParameter('cat', $category);
            }
            $query->orderBy('m.id', 'ASC')
            ->setFirstResult($index)
            ->setMaxResults($pageSize)
        ;
        return $query->getQuery()
            ->getResult();
    }


    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countModels($category): int
    {
        $query = $this->createQueryBuilder('m')->select('count(m.id)');
        if ($category != null) {
            $query->where('m.category = :val')
                ->setParameter('val', $category);
        }
        return $query->getQuery()
            ->getSingleScalarResult();
    }

}
