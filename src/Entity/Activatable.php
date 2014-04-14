<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Activatable
{
    /**
     * Activate the entity
     *
     * @return void
     */
    function activate();

    /**
     * Deactivate the entity
     *
     * @return void
     */
    function deactivate();

    /**
     * Default notification for activated entity
     *
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function activatedNotification();

    /**
     * Default notification for deactivated entity
     *
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function deactivatedNotification();
}