<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Deletable
{
    /**
     * Default message for deleted entity
     *
     * If message is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onDeleteMessage();
}