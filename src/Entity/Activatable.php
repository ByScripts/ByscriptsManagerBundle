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
     * Message for activated entity
     *
     * If message is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onActivateMessage();

    /**
     * Message for deactivated entity
     *
     * If message is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onDeactivateMessage();
}