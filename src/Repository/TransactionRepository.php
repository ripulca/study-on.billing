<?php

namespace App\Repository;

use App\Enum\CourseEnum;
use App\Entity\Transaction;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function save(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilter($user, $filter){
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.customer = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('t.created');

        if ($filter['type']!=null) {
            $query->andWhere('t.type = :type')->setParameter('type', $filter['type']);
        }

        if ($filter['course_code']!=null) {
            $query->andWhere('c.code = :code')->setParameter('code', $filter['course_code']);
        }

        if ($filter['skip_expired']!=null) {
            $query->andWhere('t.expires IS NULL or t.expires >= :today')
                ->setParameter('today', new \DateTime());
        }
        return $query->getQuery()->getResult();
    }

    public function ifCoursePaid($course, $user){
        $query = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.course', 'c')
            ->where('c.id = :courseId')
            ->setParameter('courseId', $course->getId())

            ->innerJoin('t.customer', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $user->getId())
        ;

        if ($course->getType() === CourseEnum::RENT) {
            $query->andWhere('t.expires > :now')
                ->setParameter('now', new \DateTime());
        }

        return $query
            ->getQuery()
            ->getSingleScalarResult();
    }
//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
