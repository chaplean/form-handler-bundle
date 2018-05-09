<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Exception\Exception;

/**
 * Interface FailureHandlerInterface.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     4.1.0
 */
interface ExceptionHandlerInterface
{
    /**
     * Logic to run when the success or failure handler raised an exception.
     *
     * @param Exception $exception
     *
     * @return mixed Data used to build the response to the user
     */
    public function onException(Exception $exception);
}
