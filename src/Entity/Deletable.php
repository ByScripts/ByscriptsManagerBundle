<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Deletable
{
    /**
     * Default notification for when entity is deleted
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onDeleteSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while deleting entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param \Exception $exception
     * @param array      $options
     *
     * @return string|array
     */
    function onDeleteErrorNotification(\Exception $exception, array $options = array());
}