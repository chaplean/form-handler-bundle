<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ControllerFormHandler
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class ControllerFormHandler
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $successHandlerContainerId = 'chaplean_form_handler.success_handler.persister';

    /** @var string */
    protected $failureHandlerContainerId = 'chaplean_form_handler.failure_handler.angular';

    /** @var string */
    protected $exceptionHandlerContainerId;

    /** @var string */
    protected $preprocessorContainerId;

    /** @var string */
    protected $customValidatorContainerId;

    /** @var ControllerSuccessHandler */
    protected $successHandler;

    /** @var ControllerFailureHandler */
    protected $failureHandler;

    /** @var ControllerExceptionHandler */
    protected $exceptionHandler;

    /** @var ViewHandlerInterface */
    protected $viewHandler;

    /** @var array */
    protected $succesParameters = [];

    /** @var array */
    protected $preprocessorParameters = [];

    /** @var array */
    protected $failureParameters = [];

    /**
     * ControllerFormHandler constructor.
     *
     * @param ContainerInterface         $container
     * @param FormHandler                $formHandler
     * @param ControllerSuccessHandler   $successHandler
     * @param ControllerFailureHandler   $failureHandler
     * @param ControllerExceptionHandler $exceptionHandler
     * @param ViewHandlerInterface       $viewHandler
     */
    public function __construct(
        ContainerInterface $container,
        FormHandler $formHandler,
        ControllerSuccessHandler $successHandler,
        ControllerFailureHandler $failureHandler,
        ControllerExceptionHandler $exceptionHandler,
        ViewHandlerInterface $viewHandler
    ) {
        $this->container = $container;
        $this->formHandler = $formHandler;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->exceptionHandler = $exceptionHandler;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Name in the container of the SuccessHandler you want to setup
     *
     * @param string $successHandler
     * @param array  $parameters
     *
     * @return self
     */
    public function successHandler(string $successHandler, array $parameters = []): self
    {
        $this->successHandlerContainerId = $successHandler;
        $this->succesParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the FailureHandler you want to setup
     *
     * @param string $failureHandler
     * @param array  $parameters
     *
     * @return self
     */
    public function failureHandler(string $failureHandler, array $parameters = []): self
    {
        $this->failureHandlerContainerId = $failureHandler;
        $this->failureParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the ExceptionHandler you want to setup
     *
     * @param string $exceptionHandler
     *
     * @return self
     */
    public function exceptionHandler(string $exceptionHandler): self
    {
        $this->exceptionHandlerContainerId = $exceptionHandler;

        return $this;
    }

    /**
     * Name in the container of the Preprocessor you want to setup
     *
     * @param string $preprocessor
     * @param array  $parameters
     *
     * @return self
     */
    public function preprocessor(string $preprocessor, array $parameters = []): self
    {
        $this->preprocessorContainerId = $preprocessor;
        $this->preprocessorParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the Validator you want to setup
     *
     * @param string $validator
     *
     * @return self
     */
    public function validator(string $validator): self
    {
        $this->customValidatorContainerId = $validator;

        return $this;
    }

    /**
     * List of groups you want when serializing your entity in the success case
     *
     * @param array $groups
     *
     * @return self
     */
    public function setGroups(array $groups): self
    {
        $this->successHandler->setGroups($groups);

        return $this;
    }

    /**
     * Handle the form processing
     *
     * @param string  $formContainerId The form service name in the container
     * @param object  $entity          The entity (doctrine entity, some model, whatever the form uses)
     * @param Request $request         The request
     *
     * @return Response
     */
    public function handle(string $formContainerId, $entity, Request $request): Response
    {
        $successHandler = $this->container->get($this->successHandlerContainerId);
        $failureHandler = $this->container->get($this->failureHandlerContainerId);
        $exceptionHandler = null;
        $preprocessor = null;
        $customValidator = null;

        if ($this->exceptionHandlerContainerId !== null) {
            $exceptionHandler = $this->container->get($this->exceptionHandlerContainerId);
        }

        if ($this->preprocessorContainerId !== null) {
            $preprocessor = $this->container->get($this->preprocessorContainerId);
        }

        if ($this->customValidatorContainerId !== null) {
            $customValidator = $this->container->get($this->customValidatorContainerId);
        }

        if (!$successHandler instanceof SuccessHandlerInterface) {
            throw new \InvalidArgumentException("'" . $this->successHandlerContainerId . "' is supposed to implement " . SuccessHandlerInterface::class);
        }

        if (!$failureHandler instanceof FailureHandlerInterface) {
            throw new \InvalidArgumentException("'" . $this->failureHandlerContainerId . "' is supposed to implement " . FailureHandlerInterface::class);
        }

        if ($exceptionHandler !== null && !$exceptionHandler instanceof ExceptionHandlerInterface) {
            throw new \InvalidArgumentException("'" . $this->exceptionHandlerContainerId . "' is supposed to implement " . ExceptionHandlerInterface::class);
        }

        if ($preprocessor !== null && !$preprocessor instanceof PreprocessorInterface) {
            throw new \InvalidArgumentException("'" . $this->preprocessorContainerId . "' is supposed to implement " . PreprocessorInterface::class);
        }

        if ($customValidator !== null && !$customValidator instanceof ValidatorInterface) {
            throw new \InvalidArgumentException("'" . $this->customValidatorContainerId . "' is supposed to implement " . ValidatorInterface::class);
        }

        $this->successHandler->setHandler($successHandler);
        $this->failureHandler->setHandler($failureHandler);
        $this->exceptionHandler->setHandler($exceptionHandler);

        $this->formHandler->successHandler($this->successHandler, $this->succesParameters);
        $this->formHandler->failureHandler($this->failureHandler, $this->failureParameters);
        $this->formHandler->exceptionHandler($this->exceptionHandler);

        if ($preprocessor !== null) {
            $this->formHandler->preprocessor($preprocessor, $this->preprocessorParameters);
        }

        if ($customValidator !== null) {
            $this->formHandler->validator($customValidator);
        }

        $data = $request->request->all();
        if ($request->getMethod() === 'GET') {
            $data = $request->query->all();
        }

        $view = $this->formHandler->handle($formContainerId, $entity, $data);

        return $this->viewHandler->handle($view, $request);
    }
}
