<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

use Byscripts\Bundle\ManagerBundle\Entity\Activatable;
use Byscripts\Bundle\ManagerBundle\Entity\Deletable;
use Byscripts\Bundle\ManagerBundle\Entity\Savable;
use Byscripts\Bundle\ManagerBundle\Notifier\NotifierInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class AbstractManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var NotifierInterface
     */
    protected $notifier;

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setNotifier(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * @return EntityRepository
     */
    abstract public function getRepository();

    /**
     * Alias to repository find method
     *
     * @param      $id
     * @param int  $lockMode
     * @param null $lockVersion
     *
     * @return null|object
     */
    final public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository()->find($id, $lockMode, $lockVersion);
    }

    /**
     * Alias to repository findAll method
     *
     * @return array
     */
    final public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Alias to repository findBy method
     *
     * @param array $criteria
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     *
     * @return array
     */
    final public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Alias to repository findOneBy method
     *
     * @param array $criteria
     * @param array $orderBy
     *
     * @return null|object
     */
    final public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * Alias to entity manager persist method
     *
     * @param $entity
     *
     * @return $this
     */
    final public function persist($entity)
    {
        $this->entityManager->persist($entity);

        return $this;
    }

    /**
     * Alias to entity manager remove method
     *
     * @param $entity
     *
     * @return $this
     */
    final public function remove($entity)
    {
        $this->entityManager->remove($entity);

        return $this;
    }

    /**
     * Alias to entity manager flush method
     *
     * @return $this
     */
    final public function flush()
    {
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Save the entity to the database
     *
     * @param object $entity
     * @param array  $options
     */
    public function save($entity, array $options = array())
    {
        $isNew = !boolval($entity->getId());

        $this->persist($entity)->flush();

        $this->onSaved($entity, $isNew, $options);
    }

    /**
     * Delete the entity from the database
     *
     * @param       $entity
     * @param array $options
     */
    public function delete($entity, array $options = array())
    {
        $this->remove($entity)->flush();

        $this->onDeleted($entity, $options);
    }

    /**
     * Delete the entity from the database
     *
     * @param       $entity
     * @param array $options
     *
     * @throws \Exception
     */
    public function activate($entity, array $options = array())
    {
        if (!$entity instanceof Activatable) {
            throw new \Exception(sprintf('Entity %s must implements Activatable interface'));
        }

        $entity->activate();
        $this->persist($entity)->flush();

        $this->onActivated($entity, $options);
    }

    /**
     * Triggered after the entity is deleted
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDeleted($entity, array $options)
    {
        if (array_key_exists('onDeleteMessage', $options)) {
            $this->notify($options['onDeleteMessage']);
        } elseif ($entity instanceof Deletable) {
            $this->notify($entity->onDeleteMessage());
        }
    }

    /**
     * Triggered after the entity is deleted
     *
     * @param       $entity
     * @param array $options
     */
    protected function onActivated($entity, array $options)
    {
        if (array_key_exists('onActivateMessage', $options)) {
            $this->notify($options['onActivateMessage']);
        } elseif ($entity instanceof Deletable) {
            $this->notify($entity->onDeleteMessage());
        }
    }

    /**
     * Triggered after the entity is saved
     *
     * @param       $entity
     * @param       $isNew
     * @param array $options
     */
    protected function onSaved($entity, $isNew, array $options)
    {
        if ($isNew) {
            if (array_key_exists('onCreateMessage', $options)) {
                $this->notify($options['onCreateMessage']);
            } elseif ($entity instanceof Savable) {
                $this->notify($entity->onCreateMessage());
            }
        } else {
            if (array_key_exists('onUpdateMessage', $options)) {
                $this->notify($options['onUpdateMessage']);
            } elseif ($entity instanceof Savable) {
                $this->notify($entity->onUpdateMessage());
            }
        }
    }

    /**
     * @param string|array $message
     */
    protected function notify($message)
    {
        if (null !== $this->notifier && !empty($message)) {
            if (is_array($message)) {
                $message = vsprintf(array_shift($message), $message);
            }
            $this->notifier->notify($message);
        }
    }
}