<?php


namespace Byscripts\Bundle\ManagerBundle\Notifier;

interface NotifierInterface
{
    /** Handle a success message */
    public function notifySuccess($message);

    /** Handle an error message */
    public function notifyError($message);
}