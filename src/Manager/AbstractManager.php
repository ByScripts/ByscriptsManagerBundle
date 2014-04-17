<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

use Byscripts\Bundle\ManagerBundle\Entity\Activatable;
use Byscripts\Bundle\ManagerBundle\Entity\Creatable;
use Byscripts\Bundle\ManagerBundle\Entity\Deactivatable;
use Byscripts\Bundle\ManagerBundle\Entity\Deletable;
use Byscripts\Bundle\ManagerBundle\Entity\Duplicatable;
use Byscripts\Bundle\ManagerBundle\Entity\Updatable;
use Byscripts\Bundle\ManagerBundle\Notifier\NotifierInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class AbstractManager
{
    const SKIP_CREATE_SUCCESS_NOTIFICATION = 1;
    const SKIP_CREATE_ERROR_NOTIFICATION   = 2;

    const SKIP_UPDATE_SUCCESS_NOTIFICATION = 4;
    const SKIP_UPDATE_ERROR_NOTIFICATION   = 8;

    const SKIP_DELETE_SUCCESS_NOTIFICATION = 16;
    const SKIP_DELETE_ERROR_NOTIFICATION   = 32;

    const SKIP_ACTIVATE_SUCCESS_NOTIFICATION = 64;
    const SKIP_ACTIVATE_ERROR_NOTIFICATION   = 128;

    const SKIP_DUPLICATE_SUCCESS_NOTIFICATION = 256;
    const SKIP_DUPLICATE_ERROR_NOTIFICATION   = 512;

    /** CREATE SUCCESS + CREATE ERROR */
    const SKIP_CREATE_NOTIFICATION = 3;

    /** UPDATE SUCCESS + UPDATE ERROR */
    const SKIP_UPDATE_NOTIFICATION = 12;

    /** CREATE SUCCESS + UPDATE SUCCESS */
    const SKIP_SAVE_SUCCESS_NOTIFICATION = 5;

    /** CREATE ERROR + UPDATE ERROR */
    const SKIP_SAVE_ERROR_NOTIFICATION = 10;

    /** CREATE SUCCESS + CREATE ERROR + UPDATE SUCCESS + UPDATE ERROR */
    const SKIP_SAVE_NOTIFICATION = 15;

    /** DELETE SUCCESS + DELETE ERROR */
    const SKIP_DELETE_NOTIFICATION = 48;

    /** ACTIVATE SUCCESS + ACTIVATE ERROR */
    const SKIP_ACTIVATE_NOTIFICATION = 192;

    /** DUPLICATE SUCCESS + DUPLICATE ERROR */
    const SKIP_DUPLICATE_NOTIFICATION = 768;

    /** ALL SUCCESS NOTIFICATIONS */
    const SKIP_SUCCESS_NOTIFICATION = 341;

    /** ALL ERROR NOTIFICATIONS */
    const SKIP_ERROR_NOTIFICATION = 682;

    /** ALL NOTIFICATIONS */
    const SKIP_NOTIFICATION = 1023;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var NotifierInterface
     */
    protected $notifier;

    protected $exceptions = array();

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
     * @return bool
     */
    public function duplicate($entity, array $options = array(), $flags = 0)
    {
        try {
            if (!$entity instanceof Duplicatable) {
                throw new \Exception(sprintf('Entity %s must implements Duplicatable interface'));
            }
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
     * @return bool
     */
    public function activate($entity, array $options = array(), $flags = 0)
    {
        try {
            if (!$entity instanceof Activatable) {
                throw new \Exception(sprintf('Entity %s must implements Activatable interface'));
            }
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
     * @return bool
     */
    public function deactivate($entity, array $options = array(), $flags = 0)
    {
        try {

            if (!$entity instanceof Deactivatable) {
                throw new \Exception(sprintf('Entity %s must implements Deactivatable interface'));
            }

            $entity->deactivate($options);
            $this->persist($entity)->flush();
            $this->onDeactivateSuccess($entity, $options, $flags);

            return true;
        } catch (\Exception $exception) {
            $this->onDeactivateError($exception, $entity, $options, $flags);

            return false;
        }
    }

    private function isExceptionSupported(\Exception $exception)
    {
        foreach ($this->exceptions as $supportedException) {
            if ($exception instanceof $supportedException) {
                return true;
            }
        }

        return false;
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
        if ($flags & self::SKIP_CREATE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onCreateSuccessNotification', $options)) {
            $this->notifySuccess($options['onCreateSuccessNotification']);
        } elseif ($entity instanceof Creatable) {
            $this->notifySuccess($entity->onCreateSuccessNotification($options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_CREATE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onCreateErrorNotification', $options)) {
            $this->notifyError($options['onCreateErrorNotification']);
        } elseif ($entity instanceof Creatable) {
            $this->notifyError($entity->onCreateErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
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
        if ($flags & self::SKIP_UPDATE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onUpdateSuccessNotification', $options)) {
            $this->notifySuccess($options['onUpdateSuccessNotification']);
        } elseif ($entity instanceof Updatable) {
            $this->notifySuccess($entity->onUpdateSuccessNotification($options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_UPDATE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onUpdateErrorNotification', $options)) {
            $this->notifyError($options['onUpdateErrorNotification']);
        } elseif ($entity instanceof Updatable) {
            $this->notifyError($entity->onUpdateErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
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
        if ($flags & self::SKIP_DELETE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onDeleteSuccessNotification', $options)) {
            $this->notifySuccess($options['onDeleteSuccessNotification']);
        } elseif ($entity instanceof Deletable) {
            $this->notifySuccess($entity->onDeleteSuccessNotification($options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_DELETE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onDeleteErrorNotification', $options)) {
            $this->notifyError($options['onDeleteErrorNotification']);
        } elseif ($entity instanceof Deletable) {
            $this->notifyError($entity->onDeleteErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
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
        if ($flags & self::SKIP_ACTIVATE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onActivateSuccessNotification', $options)) {
            $this->notifySuccess($options['onActivateSuccessNotification']);
        } elseif ($entity instanceof Activatable) {
            $this->notifySuccess($entity->onActivateSuccessNotification($options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_ACTIVATE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onActivateErrorNotification', $options)) {
            $this->notifyError($options['onActivateErrorNotification']);
        } elseif ($entity instanceof Activatable) {
            $this->notifyError($entity->onActivateErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
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
        if ($flags & self::SKIP_ACTIVATE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onDeactivateSuccessNotification', $options)) {
            $this->notifySuccess($options['onDeactivateSuccessNotification']);
        } elseif ($entity instanceof Deactivatable) {
            $this->notifySuccess($entity->onDeactivateSuccessNotification($options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_ACTIVATE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onDeactivateErrorNotification', $options)) {
            $this->notifyError($options['onDeactivateErrorNotification']);
        } elseif ($entity instanceof Deactivatable) {
            $this->notifyError($entity->onDeactivateErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
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
        if ($flags & self::SKIP_DUPLICATE_SUCCESS_NOTIFICATION) {
            return;
        }

        if (array_key_exists('onDuplicateSuccessNotification', $options)) {
            $this->notifySuccess($options['onDuplicateSuccessNotification']);
        } elseif ($entity instanceof Duplicatable) {
            $this->notifySuccess($entity->onDuplicateSuccessNotification($duplicate, $options));
        }
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
        if (!$this->isExceptionSupported($exception) || ($flags & self::SKIP_DUPLICATE_ERROR_NOTIFICATION)) {
            return;
        }

        if (array_key_exists('onDuplicateErrorNotification', $options)) {
            $this->notifyError($options['onDuplicateErrorNotification']);
        } elseif ($entity instanceof Duplicatable) {
            $this->notifyError($entity->onDuplicateErrorNotification($exception, $options));
        } else {
            throw $exception;
        }
    }

    /**
     * @param string|array $message
     * @param int          $flags
     */
    protected function notifySuccess($message, $flags = 0)
    {
        if ($flags & self::SKIP_SUCCESS_NOTIFICATION) {
            return;
        }

        if (null !== $this->notifier) {
            $this->notifier->notifySuccess($this->parseMessage($message));
        }
    }

    /**
     * @param string|array $message
     * @param int          $flags
     */
    protected function notifyError($message, $flags = 0)
    {
        if ($flags & self::SKIP_ERROR_NOTIFICATION) {
            return;
        }

        if (null !== $this->notifier) {
            $this->notifier->notifyError($this->parseMessage($message));
        }
    }

    protected function parseMessage($message)
    {
        return is_array($message) ? vsprintf(array_shift($message), $message) : $message;
    }
}