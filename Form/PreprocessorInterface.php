<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

/**
 * Interface PreprocessorInterface.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
interface PreprocessorInterface
{
    /**
     * Apply a transformation pass on request data before it is given to the form
     *
     * @param array $data
     * @param array $parameters
     *
     * @return array
     */
    public function preprocess(array $data, array $parameters);
}
