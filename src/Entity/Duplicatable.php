<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Duplicatable
{
    /**
     * Duplicate the entity
     *
     * @param array $options
     *
     * @return object The duplicated entity
     */
    function duplicate(array $options = array());

    /**
     * Default notification for when duplicated entity is created


*
*@param Notification $notification
     * @param object $duplicate The duplicate of the entity
     * @param array  $options


*
*@return Notification
     */
    function onDuplicateSuccessNotification(Notification $notification, $duplicate, array $options = array());

    /**
     * Default notification for when error happens while creating duplicated entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onDuplicateErrorNotification(Notification $notification, array $options = array());
}