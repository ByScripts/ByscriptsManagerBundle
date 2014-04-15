<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Activatable
{
    /**
     * Activate the entity
     *
     * @param array $options
     *
     * @return void
     */
    function activate(array $options = array());

    /**
     * Default notification for when entity is activated
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onActivateSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while activating entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param \Exception $exception
     * @param array      $options
     *
     * @return string|array
     */
    function onActivateErrorNotification(\Exception $exception, array $options = array());
}