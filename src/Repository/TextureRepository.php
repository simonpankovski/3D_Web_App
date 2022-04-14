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

    public function findAllAndPaginate($index, $pageSize, $category, $searchTerm): array
    {
        $query = $this->createQueryBuilder('t');
        if($category != null){
            $query->where('t.category = :cat')
                ->setParameter('cat', $category);
        }
        if ($searchTerm != null) {
            $pattern = strtolower($searchTerm);
            $query->andWhere('LOWER(m.name) LIKE :name')->setParameter('name', '%' . $pattern . '%');
        }
        $query->orderBy('t.id', 'ASC')
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
    public function countTextures($category): int
    {
        $query = $this->createQueryBuilder('t')->select('count(t.id)');
        if ($category != null) {
            $query->where('t.category = :val')
                ->setParameter('val', $category);
        }
        return $query->getQuery()
            ->getSingleScalarResult();
    }
}
