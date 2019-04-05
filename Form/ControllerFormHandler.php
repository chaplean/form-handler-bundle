<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use FOS\RestBundle\Request\ParamFetcherInterface;
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
    protected $successHandlerContainerId = PersisterSuccessHandler::class;

    /** @var string */
    protected $failureHandlerContainerId = AngularFailureHandler::class;

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

    /** @var FormHandler */
    protected $formHandler;

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
     * @param string|SuccessHandlerInterface $successHandler
     * @param array                          $parameters
     *
     * @return self
     */
    public function successHandler($successHandler, array $parameters = []): self
    {
        $this->successHandlerContainerId = $successHandler;
        $this->succesParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the FailureHandler you want to setup
     *
     * @param string|FailureHandlerInterface $failureHandler
     * @param array                          $parameters
     *
     * @return self
     */
    public function failureHandler($failureHandler, array $parameters = []): self
    {
        $this->failureHandlerContainerId = $failureHandler;
        $this->failureParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the ExceptionHandler you want to setup
     *
     * @param string|ExceptionHandlerInterface $exceptionHandler
     *
     * @return self
     */
    public function exceptionHandler($exceptionHandler): self
    {
        $this->exceptionHandlerContainerId = $exceptionHandler;

        return $this;
    }

    /**
     * Name in the container of the Preprocessor you want to setup
     *
     * @param string|PreprocessorInterface $preprocessor
     * @param array  $parameters
     *
     * @return self
     */
    public function preprocessor($preprocessor, array $parameters = []): self
    {
        $this->preprocessorContainerId = $preprocessor;
        $this->preprocessorParameters = $parameters;

        return $this;
    }

    /**
     * Name in the container of the Validator you want to setup
     *
     * @param string|ValidatorInterface $validator
     *
     * @return self
     */
    public function validator($validator): self
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
     * @param string                        $formContainerId The form service name in the container
     * @param object                        $entity          The entity (doctrine entity, some model, whatever the form uses)
     * @param Request|ParamFetcherInterface $parameters      The request or the parameters
     *
     * @return Response
     */
    public function handle(string $formContainerId, $entity, $parameters): Response
    {
        /** @var SuccessHandlerInterface $successHandler */
        $successHandler = $this->getHandler($this->successHandlerContainerId, SuccessHandlerInterface::class);
        /** @var FailureHandlerInterface $failureHandler */
        $failureHandler = $this->getHandler($this->failureHandlerContainerId, FailureHandlerInterface::class);
        /** @var ExceptionHandlerInterface $exceptionHandler */
        $exceptionHandler = $this->getHandler($this->exceptionHandlerContainerId, ExceptionHandlerInterface::class, false);
        /** @var PreprocessorInterface $preprocessor */
        $preprocessor = $this->getHandler($this->preprocessorContainerId, PreprocessorInterface::class, false);
        /** @var ValidatorInterface $customValidator */
        $customValidator = $this->getHandler($this->customValidatorContainerId, ValidatorInterface::class, false);

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

        if ($parameters instanceof Request) {
            $request = $parameters;
            $data = $request->request->all();

            if ($request->getMethod() === 'GET') {
                $data = $request->query->all();
            }
        } else {
            $request = null;
            $data = $parameters->all();
        }

        $view = $this->formHandler->handle($formContainerId, $entity, $data);

        return $this->viewHandler->handle($view, $request);
    }

    /**
     * @param string|object|null $handlerContainerId
     * @param string             $expectedClass
     * @param bool               $mandatory
     *
     * @return object|null
     */
    private function getHandler($handlerContainerId, string $expectedClass, bool $mandatory = true)
    {
        if ($handlerContainerId instanceof $expectedClass) {
            return $handlerContainerId;
        } else if (!$mandatory && ($handlerContainerId === null)) {
            return null;
        }

        $handler = $this->container->get($handlerContainerId);

        if ($handler !== null && !$handler instanceof $expectedClass) {
            throw new \InvalidArgumentException("'" . $handlerContainerId . "' is supposed to implement " . $expectedClass);
        }

        return $handler;
    }
}
