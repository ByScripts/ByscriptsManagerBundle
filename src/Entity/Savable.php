<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Savable
{
    /**
     * Default message for created entity
     *
     * If message is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onCreateMessage();

    /**
     * Default message for updated entity
     *
     * If message is returned as an array, it will be processed through sprintf
     *
     * @return string|array
     */
    function onUpdateMessage();
}