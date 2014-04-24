<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

use Byscripts\Notifier\Notification\Notification;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class AbstractManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected $exceptions = array();

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return bool
     */
    public function save($entity, Notification &$notification = null, array $options = array())
    {
        Notification::ensure($notification);

        $isNew = !(bool)$entity->getId();

        try {
            $this->processSave($entity);

            if ($isNew) {
                $this->onCreateSuccess($entity, $notification, $options);
            } else {
                $this->onUpdateSuccess($entity, $notification, $options);
            }

            return true;
        } catch (\Exception $exception) {
            if ($isNew) {
                $this->onCreateError($exception, $entity, $notification, $options);
            } else {
                $this->onUpdateError($exception, $entity, $notification, $options);
            }

            return false;
        }
    }

    /**
     * Delete the entity from the database
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return bool
     */
    public function delete($entity, Notification &$notification = null, array $options = array())
    {
        Notification::ensure($notification);

        try {
            $this->processDelete($entity);
            $this->onDeleteSuccess($entity, $notification, $options);

            return true;
        } catch (\Exception $exception) {
            $this->onDeleteError($exception, $entity, $notification, $options);

            return false;
        }
    }

    /**
     * Duplicate the entity
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return bool
     */
    public function duplicate($entity, Notification &$notification = null, array $options = array())
    {
        Notification::ensure($notification);

        try {
            $clone = $this->processDuplicate($entity);
            $this->onDuplicateSuccess($entity, $clone, $notification, $options);

            return true;
        } catch (\Exception $exception) {
            $this->onDuplicateError($exception, $entity, $notification, $options);

            return false;
        }
    }

    /**
     * Activate the entity
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return bool
     */
    public function activate($entity, Notification &$notification = null, array $options = array())
    {
        Notification::ensure($notification);

        try {
            $this->processActivate($entity);
            $this->onActivateSuccess($entity, $notification, $options);

            return true;
        } catch (\Exception $exception) {
            $this->onActivateError($exception, $entity, $notification, $options);

            return false;
        }
    }

    /**
     * Deactivate the entity
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return bool
     */
    public function deactivate($entity, Notification &$notification = null, array $options = array())
    {
        Notification::ensure($notification);

        try {
            $this->processDeactivate($entity);
            $this->onDeactivateSuccess($entity, $notification, $options);

            return true;
        } catch (\Exception $exception) {
            $this->onDeactivateError($exception, $entity, $notification, $options);

            return false;
        }
    }

    /**
     * @param object $entity
     */
    protected function processSave($entity)
    {
        $this->persist($entity)->flush();
    }

    protected function processDelete($entity)
    {
        $this->remove($entity)->flush();
    }

    protected function processDuplicate($entity)
    {
        $clone = clone $entity;
        $this->persist($clone)->flush();

        return $clone;
    }

    /**
     * @param object $entity
     */
    protected function processActivate($entity)
    {
        $entity->setActive(true);
        $this->persist($entity)->flush();
    }

    /**
     * @param object $entity
     */
    protected function processDeactivate($entity)
    {
        $entity->setActivate(false);
        $this->persist($entity)->flush();
    }

    /**
     * Triggered after the entity is created
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @return void
     */
    protected function onCreateSuccess($entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered if the entity can not be created
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onCreateError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity is updated
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onUpdateSuccess($entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered if the entity can not be saved
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @throws \Exception
     */
    protected function onUpdateError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity is deleted
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDeleteSuccess($entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity can not be deleted
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDeleteError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity is activated
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onActivateSuccess($entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     *
     * @throws \Exception
     */
    protected function onActivateError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity is deactivated
     *
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDeactivateSuccess($entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered if the entity cannot be deactivated
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDeactivateError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }

    /**
     * Triggered after the entity is activated
     *
     * @param object       $entity The duplicated entity
     * @param object       $clone  The duplicate of the entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDuplicateSuccess($entity, $clone, Notification $notification, array $options)
    {
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param \Exception   $exception
     * @param object       $entity
     * @param Notification $notification
     * @param array        $options
     */
    protected function onDuplicateError(\Exception $exception, $entity, Notification $notification, array $options)
    {
    }
}