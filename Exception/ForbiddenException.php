<?php

namespace Chaplean\Bundle\FormHandlerBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ForbiddenException.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Exception
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class ForbiddenException extends Exception
{
    /**
     * ForbiddenException constructor.
     *
     * @param $content
     */
    public function __construct($content = null)
    {
        parent::__construct($content, Response::HTTP_FORBIDDEN);
    }
}
