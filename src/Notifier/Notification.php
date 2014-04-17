<?php


namespace Byscripts\Bundle\ManagerBundle\Notifier;

use Exception;

abstract class Notification
{
    const SKIP_CREATE_SUCCESS = 1;
    const SKIP_CREATE_ERROR   = 2;

    const SKIP_UPDATE_SUCCESS = 4;
    const SKIP_UPDATE_ERROR   = 8;

    const SKIP_DELETE_SUCCESS = 16;
    const SKIP_DELETE_ERROR   = 32;

    const SKIP_ACTIVATE_SUCCESS = 64;
    const SKIP_ACTIVATE_ERROR   = 128;

    const SKIP_DUPLICATE_SUCCESS = 256;
    const SKIP_DUPLICATE_ERROR   = 512;

    /** CREATE SUCCESS + CREATE ERROR */
    const SKIP_CREATE = 3;

    /** UPDATE SUCCESS + UPDATE ERROR */
    const SKIP_UPDATE = 12;

    /** CREATE SUCCESS + UPDATE SUCCESS */
    const SKIP_SAVE_SUCCESS = 5;

    /** CREATE ERROR + UPDATE ERROR */
    const SKIP_SAVE_ERROR = 10;

    /** CREATE SUCCESS + CREATE ERROR + UPDATE SUCCESS + UPDATE ERROR */
    const SKIP_SAVE = 15;

    /** DELETE SUCCESS + DELETE ERROR */
    const SKIP_DELETE = 48;

    /** ACTIVATE SUCCESS + ACTIVATE ERROR */
    const SKIP_ACTIVATE = 192;

    /** DUPLICATE SUCCESS + DUPLICATE ERROR */
    const SKIP_DUPLICATE = 768;

    /** ALL SUCCESS NOTIFICATIONS */
    const SKIP_SUCCESS = 341;

    /** ALL ERROR NOTIFICATIONS */
    const SKIP_ERROR = 682;

    /** ALL NOTIFICATIONS */
    const SKIP = 1023;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var bool
     */
    private $exceptionEnabled = false;

    /**
     * @var string
     */
    private $message;

    /**
     * @param string $message
     */
    public function __construct($message = '')
    {
        $this->message = $message;
    }

    /**
     * @param \Exception $exception
     * @param bool       $enabled
     *
     * @return $this
     */
    public function setException(\Exception $exception, $enabled = false)
    {
        $this->exception = $exception;
        $this->exceptionEnabled = $enabled;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableException()
    {
        $this->exceptionEnabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableException()
    {
        $this->exceptionEnabled = false;

        return $this;
    }
}