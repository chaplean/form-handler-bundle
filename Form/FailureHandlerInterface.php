<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Symfony\Component\Form\FormErrorIterator;

/**
 * Interface FailureHandlerInterface.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
interface FailureHandlerInterface
{
    /**
     * Logic to run when the validation raised errors
     *
     * @param FormErrorIterator $formErrors
     * @param array             $customErrors
     * @param array             $parameters
     *
     * @return mixed Data used to build the response to the user
     */
    public function onFailure(FormErrorIterator $formErrors, array $customErrors, array $parameters);
}
