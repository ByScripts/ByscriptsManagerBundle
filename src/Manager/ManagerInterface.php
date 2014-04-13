<?php


namespace Byscripts\Bundle\ManagerBundle\Manager;

interface ManagerInterface
{
    function save($entity, array $options = array());
}