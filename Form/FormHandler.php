<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormHandler
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 */
class FormHandler
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $successHandlerContainerId = 'chaplean_form_handler.success_handler.persister';

    /** @var string */
    protected $failureHandlerContainerId = 'chaplean_form_handler.failure_handler.angular';

    /** @var string */
    protected $preprocessorContainerId;

    /** @var string */
    protected $customValidatorContainerId;

    /** @var SuccessHandlerInterface */
    protected $successHandler;

    /** @var FailureHandlerInterface */
    protected $failureHandler;

    /** @var PreprocessorInterface */
    protected $preprocessor;

    /** @var ValidatorInterface */
    protected $customValidator;

    /** @var array */
    protected $groups = [];

    /** @var EntityManager */
    protected $em;

    /** @var FormFactory */
    protected $formFactory;

    /** @var ViewHandlerInterface */
    protected $viewHandler;

    /**
     * FormHandler constructor.
     *
     * @param ContainerInterface   $container
     * @param Registry             $registry
     * @param FormFactory          $formFactory
     * @param ViewHandlerInterface $viewHandler
     */
    public function __construct(ContainerInterface $container, Registry $registry, FormFactory $formFactory, ViewHandlerInterface $viewHandler)
    {
        $this->container = $container;
        $this->em = $registry->getManager();
        $this->formFactory = $formFactory;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Name in the container of the SuccessHandler you want to setup
     *
     * @param string $successHandler
     *
     * @return $this
     */
    public function successHandler(string $successHandler) : self
    {
        $this->successHandlerContainerId = $successHandler;

        return $this;
    }

    /**
     * Name in the container of the FailureHandler you want to setup
     *
     * @param string $failureHandler
     *
     * @return $this
     */
    public function failureHandler(string $failureHandler) : self
    {
        $this->failureHandlerContainerId = $failureHandler;

        return $this;
    }

    /**
     * Name in the container of the Preprocessor you want to setup
     *
     * @param string $preprocessor
     *
     * @return $this
     */
    public function preprocessor(string $preprocessor) : self
    {
        $this->preprocessorContainerId = $preprocessor;

        return $this;
    }

    /**
     * Name in the container of the Validator you want to setup
     *
     * @param string $validator
     *
     * @return $this
     */
    public function validator(string $validator) : self
    {
        $this->customValidatorContainerId = $validator;

        return $this;
    }

    /**
     * List of groups you want JMS serializer to use when serializing your entity in the success case
     *
     * @param array $groups
     *
     * @return $this
     */
    public function setGroups(array $groups) : self
    {
        $this->groups = $groups;

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
    public function handle(string $formContainerId, $entity, Request $request) : Response
    {
        $this->constructHandlers();

        $form = $this->formFactory->create($formContainerId, $entity);

        $data = $request->request->all();
        if ($this->preprocessor !== null) {
            $data = $this->preprocessor->preprocess($data);
        }

        $customValidation = true;
        if ($this->customValidator !== null) {
            $customValidation = $this->customValidator->validate($data);
        }

        $form->submit($data);
        $formValidation = $form->isValid();

        if ($formValidation && $customValidation) {
            $entity = $this->successHandler->onSuccess($form->getData());
            $this->em->flush();

            $view = View::create($entity);

            if (!empty($this->groups)) {
                $context = new Context();
                $context->setGroups($this->groups);
                $view->setContext($context);
            }
        } else {
            $customErrors = $this->customValidator !== null
                ? $this->customValidator->getErrors()
                : [];

            $data = $this->failureHandler->onFailure($form->getErrors(true), $customErrors);
            $view = View::create($data, Response::HTTP_BAD_REQUEST);
        }

        return $this->viewHandler->handle($view, $request);
    }

    /**
     * Load from the container the needed handlers (successHandler, failureHandler, preprocessor and customValidator)
     *
     * @return void
     */
    protected function constructHandlers()
    {
        $this->successHandler = $this->container->get($this->successHandlerContainerId);
        $this->failureHandler = $this->container->get($this->failureHandlerContainerId);

        if ($this->preprocessorContainerId !== null) {
            $this->preprocessor = $this->container->get($this->preprocessorContainerId);
        } else if ($this->successHandler instanceof PreprocessorInterface) {
            $this->preprocessor = $this->successHandler;
        }

        if ($this->customValidatorContainerId !== null) {
            $this->customValidator = $this->container->get($this->customValidatorContainerId);
        } else if ($this->successHandler instanceof ValidatorInterface) {
            $this->customValidator = $this->successHandler;
        }

        if (!$this->successHandler instanceof SuccessHandlerInterface) {
            throw new \InvalidArgumentException("'" . $this->successHandlerContainerId . "' is supposed to implement " . SuccessHandlerInterface::class);
        }

        if (!$this->failureHandler instanceof FailureHandlerInterface) {
            throw new \InvalidArgumentException("'" . $this->failureHandlerContainerId . "' is supposed to implement " . FailureHandlerInterface::class);
        }

        if ($this->preprocessor !== null && !$this->preprocessor instanceof PreprocessorInterface) {
            throw new \InvalidArgumentException("'" . $this->preprocessorContainerId . "' is supposed to implement " . PreprocessorInterface::class);
        }

        if ($this->customValidator !== null && !$this->customValidator instanceof ValidatorInterface) {
            throw new \InvalidArgumentException("'" . $this->customValidatorContainerId . "' is supposed to implement " . ValidatorInterface::class);
        }
    }
}
