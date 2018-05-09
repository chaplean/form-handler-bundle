<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Exception\Exception;
use Symfony\Component\Form\FormErrorIterator;

/**
 * Class NoOperationHandler. Does nothing in all callbacks on purpose.
 *
 * @package   App\Bundle\FrontBundle\Form\Handler
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     2.1.0
 */
class NoOperationHandler implements SuccessHandlerInterface, FailureHandlerInterface, ExceptionHandlerInterface
{
    /**
     * Does nothing.
     *
     * @param FormErrorIterator $formErrors
     * @param array             $customErrors
     * @param array             $parameters
     *
     * @return void
     */
    public function onFailure(FormErrorIterator $formErrors, array $customErrors, array $parameters)
    {
    }

    /**
     * Does nothing.
     *
     * @param mixed $data
     * @param array $parameters
     *
     * @return void
     */
    public function onSuccess($data, array $parameters)
    {
    }

    /**
     * Does nothing.
     *
     * @param Exception $exception
     *
     * @return void
     */
    public function onException(Exception $exception)
    {
    }
}
