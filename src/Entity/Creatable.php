<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Creatable
{
    /**
     * Default notification for when entity is created


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onCreateSuccessNotification(Notification $notification, array $options = array());

    /**
     * Default notification for when error happens while creating entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onCreateErrorNotification(Notification $notification, array $options = array());
}