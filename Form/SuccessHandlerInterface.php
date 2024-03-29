<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

/**
 * Interface SuccessHandlerInterface.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
interface SuccessHandlerInterface
{
    /**
     * Logic to run when the form is valid
     *
     * If you need database save, this function is responsible for persist() calls
     * but not for flush(). Its called right after this function.
     *
     * @param mixed $data
     * @param array $parameters
     *
     * @return mixed Data used to build the response to the user
     */
    public function onSuccess($data, array $parameters);
}
