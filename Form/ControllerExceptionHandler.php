<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Exception\Exception;
use FOS\RestBundle\View\View;

/**
 * Class ControllerExceptionHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class ControllerExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var ExceptionHandlerInterface
     */
    protected $handler;

    /**
     * @param ExceptionHandlerInterface|null $handler
     *
     * @return void
     */
    public function setHandler(ExceptionHandlerInterface $handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * Logic to run when the success or failure handler raised an exception.
     *
     * @param Exception $exception
     *
     * @return mixed Data used to build the response to the user
     */
    public function onException(Exception $exception)
    {
        if ($this->handler !== null) {
            return $this->handler->onException($exception);
        }

        return View::create(['error' => $exception->getContent()], $exception->getStatusCode());
    }
}
