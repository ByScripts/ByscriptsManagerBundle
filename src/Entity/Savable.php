<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Savable
{
    /**
     * Default message for created entity
     *
     * @return string
     */
    function onCreateMessage();

    /**
     * Default message for updated entity
     *
     * @return string
     */
    function onUpdateMessage();
}