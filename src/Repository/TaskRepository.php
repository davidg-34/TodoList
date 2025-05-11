<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Récupère les tâches visibles par l'utilisateur courant.
     *
     * @param $user
     * @return Task[]
     */
    public function findTasksForUser($user)
    {
        if ($user && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->findAll();
        }
    

        return $this->createQueryBuilder('t')
            ->join('t.author', 'a')
            ->where('a.username != :anonyme')
            ->setParameter('anonyme', 'anonyme')
            ->getQuery()
            ->getResult();

        // Utilisateur normal → voit toutes les tâches ayant un auteur
        /* return $this->createQueryBuilder('t')
            ->where('t.author IS NOT NULL')
            ->getQuery()
            ->getResult(); */
    }
 
//    /**
//     * @return Task[] Returns an array of Task objects
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

//    public function findOneBySomeField($value): ?Task
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}