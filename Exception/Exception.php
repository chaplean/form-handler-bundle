<?php

namespace Chaplean\Bundle\FormHandlerBundle\Exception;

/**
 * Class Exception.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Exception
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class Exception extends \Exception
{
    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * Exception constructor.
     *
     * @param mixed   $content
     * @param integer $statusCode
     */
    public function __construct($content, $statusCode)
    {
        parent::__construct();

        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
