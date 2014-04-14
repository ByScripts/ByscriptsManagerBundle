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
     * @return string
     */
    function onActivateMessage();

    /**
     * Message for deactivated entity
     *
     * @return string
     */
    function onDeactivateMessage();
}