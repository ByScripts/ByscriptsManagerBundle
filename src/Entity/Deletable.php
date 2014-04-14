<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Deletable
{
    /**
     * Default notification for deleted entity
     *
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function deletedNotification();
}