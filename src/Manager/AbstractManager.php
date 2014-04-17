<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

use Byscripts\Bundle\ManagerBundle\Entity\Activatable;
use Byscripts\Bundle\ManagerBundle\Entity\Creatable;
use Byscripts\Bundle\ManagerBundle\Entity\Deactivatable;
use Byscripts\Bundle\ManagerBundle\Entity\Deletable;
use Byscripts\Bundle\ManagerBundle\Entity\Duplicatable;
use Byscripts\Bundle\ManagerBundle\Entity\Updatable;
use Byscripts\Bundle\ManagerBundle\Notifier\ErrorNotification;
use Byscripts\Bundle\ManagerBundle\Notifier\Notification;
use Byscripts\Bundle\ManagerBundle\Notifier\NotifierInterface;
use Byscripts\Bundle\ManagerBundle\Notifier\SuccessNotification;
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

    protected $exceptions = array();

    protected $defaultMessages = array(
        'success' => array(
            'create'     => 'Item has been successfully created',
            'update'     => 'Item has been successfully updated',
            'delete'     => 'Item has been successfully deleted',
            'activate'   => 'Item has been successfully activated',
            'deactivate' => 'Item has been successfully deactivated',
            'duplicate'  => 'Item has been successfully duplicated',
        ),
        'error'   => array(
            'create'     => 'An error has occurred while creating the item',
            'update'     => 'An error has occurred while updating the item',
            'delete'     => 'An error has occurred while deleting the item',
            'activate'   => 'An error has occurred while activating the item',
            'deactivate' => 'An error has occurred while deactivating the item',
            'duplicate'  => 'An error has occurred while duplicating the item',
        )
    );

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setNotifier(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function setExceptions(array $exceptions)
    {
        $this->exceptions = $exceptions;
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
     * @param int    $flags
     *
     * @return bool
     */
    public function save($entity, array $options = array(), $flags = 0)
    {
        $isNew = !boolval($entity->getId());

        try {
            $this->persist($entity)->flush();
            if ($isNew) {
                $this->onCreateSuccess($entity, $options, $flags);
            } else {
                $this->onUpdateSuccess($entity, $options, $flags);
            }

            return true;
        } catch (\Exception $exception) {
            if ($isNew) {
                $this->onCreateError($exception, $entity, $options, $flags);
            } else {
                $this->onUpdateError($exception, $entity, $options, $flags);
            }

            return false;
        }
    }

    /**
     * Delete the entity from the database
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     *
     * @return bool
     */
    public function delete($entity, array $options = array(), $flags = 0)
    {
        try {
            $this->remove($entity)->flush();
            $this->onDeleteSuccess($entity, $options, $flags);

            return true;
        } catch (\Exception $exception) {
            $this->onDeleteError($exception, $entity, $options, $flags);

            return false;
        }
    }

    /**
     * Duplicate the entity
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     *
     * @throws \Exception
     * @return bool
     */
    public function duplicate($entity, array $options = array(), $flags = 0)
    {
        if (!$entity instanceof Duplicatable) {
            throw new \Exception(sprintf('Entity %s must implements Duplicatable interface'));
        }

        try {
            $duplicate = $entity->duplicate($options);
            $this->persist($duplicate)->flush();
            $this->onDuplicateSuccess($entity, $duplicate, $options, $flags);

            return true;
        } catch (\Exception $exception) {
            $this->onDuplicateError($exception, $entity, $options, $flags);

            return false;
        }
    }

    /**
     * Activate the entity
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     *
     * @throws \Exception
     * @return bool
     */
    public function activate($entity, array $options = array(), $flags = 0)
    {
        if (!$entity instanceof Activatable) {
            throw new \Exception(sprintf('Entity %s must implements Activatable interface'));
        }

        try {

            $entity->activate($options);
            $this->persist($entity)->flush();
            $this->onActivateSuccess($entity, $options, $flags);

            return true;
        } catch (\Exception $exception) {
            $this->onActivateError($exception, $entity, $options, $flags);

            return false;
        }
    }

    /**
     * Deactivate the entity
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     *
     * @throws \Exception
     * @return bool
     */
    public function deactivate($entity, array $options = array(), $flags = 0)
    {
        if (!$entity instanceof Deactivatable) {
            throw new \Exception(sprintf('Entity %s must implements Deactivatable interface'));
        }

        try {
            $entity->deactivate($options);
            $this->persist($entity)->flush();
            $this->onDeactivateSuccess($entity, $options, $flags);

            return true;
        } catch (\Exception $exception) {
            $this->onDeactivateError($exception, $entity, $options, $flags);

            return false;
        }
    }

    /**
     * @param $key
     *
     * @return Notification
     */
    protected function successNotification($key)
    {
        return new SuccessNotification($this->defaultMessages['success'][$key]);
    }

    /**
     * @param            $key
     * @param \Exception $exception
     *
     * @return Notification
     */
    protected function errorNotification($key, \Exception $exception)
    {
        $notification = new ErrorNotification($this->defaultMessages['error'][$key]);
        return $notification->setException($exception, $this->isExceptionSupported($exception));
    }

    /**
     * Triggered after the entity is created
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     */
    protected function onCreateSuccess($entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_CREATE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('create');

        if ($entity instanceof Creatable) {
            $entity->onCreateSuccessNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered if the entity can not be created
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onCreateError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_CREATE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('create', $exception);

        if ($entity instanceof Creatable) {
            $entity->onCreateErrorNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered after the entity is updated
     *
     * @param       $entity
     * @param array $options
     * @param       $flags
     */
    protected function onUpdateSuccess($entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_UPDATE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('update');

        if ($entity instanceof Updatable) {
            $entity->onUpdateSuccessNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered if the entity can not be saved
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onUpdateError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_UPDATE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('update', $exception);

        if ($entity instanceof Updatable) {
            $entity->onUpdateErrorNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered after the entity is deleted
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     */
    protected function onDeleteSuccess($entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_DELETE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('delete');

        if ($entity instanceof Deletable) {
            $entity->onDeleteSuccessNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered after the entity can not be deleted
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onDeleteError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_DELETE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('delete', $exception);

        if ($entity instanceof Deletable) {
            $entity->onDeleteErrorNotification($notification, $options);
        }

        $this->notify($notification);
    }

    /**
     * Triggered after the entity is activated
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     */
    protected function onActivateSuccess($entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_ACTIVATE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('activate');

        if ($entity instanceof Activatable) {
            $entity->onActivateSuccessNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onActivateError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_ACTIVATE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('activate', $exception);

        if ($entity instanceof Activatable) {
            $entity->onActivateErrorNotification($notification, $options);
        }

        $this->notify($notification);
    }

    /**
     * Triggered after the entity is deactivated
     *
     * @param       $entity
     * @param array $options
     * @param int   $flags
     */
    protected function onDeactivateSuccess($entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_ACTIVATE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('deactivate');

        if ($entity instanceof Deactivatable) {
            $entity->onDeactivateSuccessNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered if the entity cannot be deactivated
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onDeactivateError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_ACTIVATE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('deactivate', $exception);

        if ($entity instanceof Deactivatable) {
            $entity->onDeactivateErrorNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered after the entity is activated
     *
     * @param object $entity    The duplicated entity
     * @param object $duplicate The duplicate of the entity
     * @param array  $options
     * @param int    $flags
     */
    protected function onDuplicateSuccess($entity, $duplicate, array $options, $flags)
    {
        if ($flags & Notification::SKIP_DUPLICATE_SUCCESS) {
            return;
        }

        $notification = $this->successNotification('duplicate');

        if ($entity instanceof Duplicatable) {
            $entity->onDuplicateSuccessNotification($notification, $duplicate, $options);
        }

        $this->notify($notification, $flags);
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param \Exception $exception
     * @param            $entity
     * @param array      $options
     * @param int        $flags
     *
     * @throws \Exception
     */
    protected function onDuplicateError(\Exception $exception, $entity, array $options, $flags)
    {
        if ($flags & Notification::SKIP_DUPLICATE_ERROR) {
            return;
        }

        $notification = $this->errorNotification('duplicate', $exception);

        if ($entity instanceof Duplicatable) {
            $entity->onDuplicateErrorNotification($notification, $options);
        }

        $this->notify($notification, $flags);
    }

    private function notify(Notification $notification)
    {
        if (null === $this->notifier) {
            return;
        }

        if ($notification instanceof SuccessNotification) {
            $this->notifier->notifySuccess($notification);
        } elseif ($notification instanceof ErrorNotification) {
            $this->notifier->notifyError($notification);
        }
    }

    private function isExceptionSupported(\Exception $exception = null)
    {
        if (null === $exception) {
            return false;
        }

        foreach ($this->exceptions as $supportedException) {
            if ($exception instanceof $supportedException) {
                return true;
            }
        }

        return false;
    }
}