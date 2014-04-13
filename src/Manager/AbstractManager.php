<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class AbstractManager implements ManagerInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** @return EntityRepository */
    abstract function getRepository();

    final public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository()->find($id, $lockMode, $lockVersion);
    }

    final public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    final public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    final public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    public function persist($entity)
    {
        $this->entityManager->persist($entity);

        return $this;
    }

    public function remove($entity)
    {
        $this->entityManager->remove($entity);

        return $this;
    }

    public function flush()
    {
        $this->entityManager->flush();

        return $this;
    }

    /**
     * @param object $entity
     * @param array  $options
     */
    public function save($entity, array $options = array())
    {
        $isNew = !boolval($entity->getId());

        $this->persist($entity)->flush();

        $this->onSaved($entity, $isNew, $options);
    }

    public function onSaved($entity, $isNew, array $options)
    {
    }

    public function delete($entity, array $options = array())
    {
        $this->remove($entity)->flush();

        $this->onDeleted($entity, $options);
    }

    public function onDeleted($entity, array $options)
    {
    }
}