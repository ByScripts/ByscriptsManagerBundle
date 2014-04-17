<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Updatable
{
    /**
     * Default notification for when entity is updated


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onUpdateSuccessNotification(Notification $notification, array $options = array());

    /**
     * Default notification for when error happens while updating entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onUpdateErrorNotification(Notification $notification, array $options = array());
}