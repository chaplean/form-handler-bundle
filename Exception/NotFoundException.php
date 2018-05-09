<?php

namespace Chaplean\Bundle\FormHandlerBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class NotFoundException.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Exception
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class NotFoundException extends Exception
{
    /**
     * NotFoundException constructor.
     *
     * @param $content
     */
    public function __construct($content = null)
    {
        parent::__construct($content, Response::HTTP_NOT_FOUND);
    }
}
