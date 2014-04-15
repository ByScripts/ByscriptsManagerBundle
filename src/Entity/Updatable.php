<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Updatable
{
    /**
     * Default notification for when entity is updated
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onUpdateSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while updating entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onUpdateErrorNotification(array $options = array());
}