<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Creatable
{
    /**
     * Default notification for when entity is created
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onCreateSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while creating entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param \Exception $exception
     * @param array      $options
     *
     * @return string|array
     */
    function onCreateErrorNotification(\Exception $exception, array $options = array());
}