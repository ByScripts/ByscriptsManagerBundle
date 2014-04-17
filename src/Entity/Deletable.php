<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Deletable
{
    /**
     * Default notification for when entity is deleted


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onDeleteSuccessNotification(Notification $notification, array $options = array());

    /**
     * Default notification for when error happens while deleting entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onDeleteErrorNotification(Notification $notification, array $options = array());
}