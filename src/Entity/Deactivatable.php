<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Deactivatable
{
    /**
     * Deactivate the entity
     *
     * @param array $options
     *
     * @return void
     */
    function deactivate(array $options = array());

    /**
     * Default notification for when entity is deactivated
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onDeactivateSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while deactivating entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param \Exception $exception
     * @param array      $options
     *
     * @return string|array
     */
    function onDeactivateErrorNotification(\Exception $exception, array $options = array());
}