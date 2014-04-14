<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Savable
{
    /**
     * Default notification for created entity
     *
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function createdNotification();

    /**
     * Default notification for updated entity
     *
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function updatedNotification();
}