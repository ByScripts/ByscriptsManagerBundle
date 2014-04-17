<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

use Byscripts\Bundle\ManagerBundle\Notifier\Notification;

interface Activatable
{
    /**
     * Activate the entity
     *
     * @param array $options
     *
     * @return void
     */
    function activate(array $options = array());

    /**
     * Default notification for when entity is activated


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onActivateSuccessNotification(Notification $notification, array $options = array());

    /**
     * Default notification for when error happens while activating entity


*
*@param Notification $notification
     * @param array               $options


*
*@return Notification
     */
    function onActivateErrorNotification(Notification $notification, array $options = array());
}