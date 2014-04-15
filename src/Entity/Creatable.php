<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Creatable
{
    /**
     * Default notification for when entity is created
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onCreateSuccessNotification();

    /**
     * Default notification for when error happens while creating entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onCreateErrorNotification();
}