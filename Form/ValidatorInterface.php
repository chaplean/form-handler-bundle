<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Symfony\Component\Form\FormErrorIterator;

/**
 * Interface ValidatorInterface.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
interface ValidatorInterface
{
    /**
     * Run some logic to add extra validation on the request data after the preprocess pass
     *
     * @param array $data
     *
     * @return boolean Whether or not the request data are considered valid
     */
    public function validate(array $data);

    /**
     * Return errors collected by validate if any
     *
     * @return array
     */
    public function getErrors();
}
