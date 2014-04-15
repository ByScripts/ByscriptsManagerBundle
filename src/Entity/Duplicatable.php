<?php


namespace Byscripts\Bundle\ManagerBundle\Entity;

interface Duplicatable
{
    /**
     * Duplicate the entity
     *
     * @return object The duplicated entity
     */
    function duplicate();

    /**
     * Default notification for when duplicated entity is created
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onDuplicateSuccessNotification(array $options = array());

    /**
     * Default notification for when error happens while creating duplicated entity
     * If notification is returned as an array, it will be processed through sprintf
     *
     * @param array $options
     *
     * @return string|array
     */
    function onDuplicateErrorNotification(array $options = array());
}