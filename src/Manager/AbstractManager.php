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
     *
     * @return bool
     */
    public function save($entity, array $options = array())
    {
        $isNew = !boolval($entity->getId());

        try {
            $this->persist($entity)->flush();
            if ($isNew) {
                $this->onCreateSuccess($entity, $options);
            } else {
                $this->onUpdateSuccess($entity, $options);
            }

            return true;
        } catch (\Exception $exception) {
            $options['exception'] = $exception;
            if ($isNew) {
                $this->onCreateError($entity, $options);
            } else {
                $this->onUpdateError($entity, $options);
            }

            return false;
        }
    }

    /**
     * Delete the entity from the database
     *
     * @param       $entity
     * @param array $options
     *
     * @return bool
     */
    public function delete($entity, array $options = array())
    {
        try {
            $this->remove($entity)->flush();
            $this->onDeleteSuccess($entity, $options);

            return true;
        } catch (\Exception $exception) {
            $options['exception'] = $exception;
            $this->onDeleteError($entity, $options);

            return false;
        }
    }

    /**
     * Duplicate the entity
     *
     * @param       $entity
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    public function duplicate($entity, array $options = array())
    {
        try {
            if (!$entity instanceof Duplicatable) {
                throw new \Exception(sprintf('Entity %s must implements Duplicatable interface'));
            }
            $duplicated = $entity->duplicate();
            $this->persist($duplicated)->flush();
            $this->onDuplicateSuccess($entity, $options);

            return true;
        } catch (\Exception $exception) {
            $option['exception'] = $exception;
            $this->onDuplicateError($entity, $options);

            return false;
        }
    }

    /**
     * Activate the entity
     *
     * @param       $entity
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    public function activate($entity, array $options = array())
    {
        try {
            if (!$entity instanceof Activatable) {
                throw new \Exception(sprintf('Entity %s must implements Activatable interface'));
            }
            $entity->activate();
            $this->persist($entity)->flush();
            $this->onActivateSuccess($entity, $options);

            return true;
        } catch (\Exception $exception) {
            $option['exception'] = $exception;
            $this->onActivateError($entity, $options);

            return false;
        }
    }

    /**
     * Deactivate the entity
     *
     * @param       $entity
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    public function deactivate($entity, array $options = array())
    {
        try {

            if (!$entity instanceof Deactivatable) {
                throw new \Exception(sprintf('Entity %s must implements Deactivatable interface'));
            }

            $entity->deactivate();
            $this->persist($entity)->flush();
            $this->onDeactivateSuccess($entity, $options);

            return true;
        } catch (\Exception $exception) {
            $options['exception'] = $exception;
            $this->onDeactivateError($entity, $options);

            return false;
        }
    }

    /**
     * Triggered after the entity is created
     *
     * @param       $entity
     * @param array $options
     */
    protected function onCreateSuccess($entity, array $options)
    {
        if (array_key_exists('onCreateSuccessNotification', $options)) {
            $this->notifySuccess($options['onCreateSuccessNotification']);
        } elseif ($entity instanceof Creatable) {
            $this->notifySuccess($entity->onCreateSuccessNotification($options));
        }
    }

    /**
     * Triggered if the entity can not be created
     *
     * @param            $entity
     * @param array      $options
     */
    protected function onCreateError($entity, array $options)
    {
        if (array_key_exists('onCreateErrorNotification', $options)) {
            $this->notifyError($options['onCreateErrorNotification']);
        } elseif ($entity instanceof Creatable) {
            $this->notifyError($entity->onCreateErrorNotification($options));
        }
    }

    /**
     * Triggered after the entity is updated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onUpdateSuccess($entity, array $options)
    {
        if (array_key_exists('onUpdateSuccessNotification', $options)) {
            $this->notifySuccess($options['onUpdateSuccessNotification']);
        } elseif ($entity instanceof Updatable) {
            $this->notifySuccess($entity->onUpdateSuccessNotification($options));
        }
    }

    /**
     * Triggered if the entity can not be saved
     *
     * @param            $entity
     * @param array      $options
     */
    protected function onUpdateError($entity, array $options)
    {
        if (array_key_exists('onUpdateErrorNotification', $options)) {
            $this->notifyError($options['onUpdateErrorNotification']);
        } elseif ($entity instanceof Updatable) {
            $this->notifyError($entity->onUpdateErrorNotification($options));
        }
    }

    /**
     * Triggered after the entity is deleted
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDeleteSuccess($entity, array $options)
    {
        if (array_key_exists('onDeleteSuccessNotification', $options)) {
            $this->notifySuccess($options['onDeleteSuccessNotification']);
        } elseif ($entity instanceof Deletable) {
            $this->notifySuccess($entity->onDeleteSuccessNotification($options));
        }
    }

    /**
     * Triggered after the entity can not be deleted
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDeleteError($entity, array $options)
    {
        if (array_key_exists('onDeleteErrorNotification', $options)) {
            $this->notifyError($options['onDeleteErrorNotification']);
        } elseif ($entity instanceof Deletable) {
            $this->notifyError($entity->onDeleteErrorNotification($options));
        }
    }

    /**
     * Triggered after the entity is activated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onActivateSuccess($entity, array $options)
    {
        if (array_key_exists('onActivateSuccessNotification', $options)) {
            $this->notifySuccess($options['onActivateSuccessNotification']);
        } elseif ($entity instanceof Activatable) {
            $this->notifySuccess($entity->onActivateSuccessNotification($options));
        }
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onActivateError($entity, array $options)
    {
        if (array_key_exists('onActivateErrorNotification', $options)) {
            $this->notifyError($options['onActivateErrorNotification']);
        } elseif ($entity instanceof Activatable) {
            $this->notifyError($entity->onActivateErrorNotification($options));
        }
    }

    /**
     * Triggered after the entity is deactivated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDeactivateSuccess($entity, array $options)
    {
        if (array_key_exists('onDeactivateSuccessNotification', $options)) {
            $this->notifySuccess($options['onDeactivateSuccessNotification']);
        } elseif ($entity instanceof Deactivatable) {
            $this->notifySuccess($entity->onDeactivateSuccessNotification($options));
        }
    }

    /**
     * Triggered if the entity cannot be deactivated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDeactivateError($entity, array $options)
    {
        if (array_key_exists('onDeactivateErrorNotification', $options)) {
            $this->notifyError($options['onDeactivateErrorNotification']);
        } elseif ($entity instanceof Deactivatable) {
            $this->notifyError($entity->onDeactivateErrorNotification($options));
        }
    }

    /**
     * Triggered after the entity is activated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDuplicateSuccess($entity, array $options)
    {
        if (array_key_exists('onDuplicateSuccessNotification', $options)) {
            $this->notifySuccess($options['onDuplicateSuccessNotification']);
        } elseif ($entity instanceof Duplicatable) {
            $this->notifySuccess($entity->onDuplicateSuccessNotification($options));
        }
    }

    /**
     * Triggered if the entity cannot be activated
     *
     * @param       $entity
     * @param array $options
     */
    protected function onDuplicateError($entity, array $options)
    {
        if (array_key_exists('onDuplicateErrorNotification', $options)) {
            $this->notifyError($options['onDuplicateErrorNotification']);
        } elseif ($entity instanceof Duplicatable) {
            $this->notifyError($entity->onDuplicateErrorNotification($options));
        }
    }

    /**
     * @param string|array $message
     */
    protected function notifySuccess($message)
    {
        if (null !== $this->notifier) {
            $this->notifier->notifyError($this->parseMessage($message));
        }
    }

    /**
     * @param string|array $message
     */
    protected function notifyError($message)
    {
        if (null !== $this->notifier) {
            $this->notifier->notifyError($this->parseMessage($message));
        }
    }

    protected function parseMessage($message)
    {
        return is_array($message) ? vsprintf(array_shift($message), $message) : $message;
    }
}