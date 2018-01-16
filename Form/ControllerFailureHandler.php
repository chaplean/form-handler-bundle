<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ControllerFailureHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class ControllerFailureHandler implements FailureHandlerInterface
{
    /**
     * @var FailureHandlerInterface
     */
    protected $handler;

    /**
     * @param FailureHandlerInterface $handler
     *
     * @return void
     */
    public function setHandler(FailureHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Logic to run when the validation raised errors
     *
     * @param FormErrorIterator $formErrors
     * @param array             $customErrors
     * @param array             $parameters
     *
     * @return View
     */
    public function onFailure(FormErrorIterator $formErrors, array $customErrors, array $parameters) : View
    {
        $result = $this->handler->onFailure($formErrors, $customErrors, $parameters);

        return View::create($result, Response::HTTP_BAD_REQUEST);
    }
}
