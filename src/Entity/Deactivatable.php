<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Deactivatable
{
    /**
     * Deactivate the entity
     *
     * @param array $options
     *
     * @return void
     */
    function deactivate(array $options = array());

    /**
     * Default notification for when entity is deactivated


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onDeactivateSuccessNotification(Notification $notification, array $options = array());

    /**
     * Default notification for when error happens while deactivating entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onDeactivateErrorNotification(Notification $notification, array $options = array());
}