<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Deletable
{
    /**
     * Default message for deleted entity
     *
     * @return string
     */
    function onDeleteMessage();
}