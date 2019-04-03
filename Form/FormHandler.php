<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Exception\Exception;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Class FormHandler
 *
 * @package   Chaplean\Bundle\FormBisHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class FormHandler
{
    /** @var SuccessHandlerInterface */
    protected $successHandler;

    /** @var FailureHandlerInterface */
    protected $failureHandler;

    /** @var ExceptionHandlerInterface */
    protected $exceptionHandler;

    /** @var PreprocessorInterface */
    protected $preprocessor;

    /** @var ValidatorInterface */
    protected $customValidator;

    /** @var FormFactory */
    protected $formFactory;

    /** @var array */
    protected $succesParameters = [];

    /** @var array */
    protected $preprocessorParameters = [];

    /** @var array */
    protected $failureParameters = [];

    /**
     * FormBisHandler constructor.
     *
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * SuccessHandlerInterface you want to setup
     *
     * @param SuccessHandlerInterface $successHandler
     * @param array                   $parameters
     *
     * @return self
     */
    public function successHandler(SuccessHandlerInterface $successHandler, array $parameters = []): self
    {
        $this->successHandler = $successHandler;
        $this->succesParameters = $parameters;

        return $this;
    }

    /**
     * FailureHandlerInterface you want to setup
     *
     * @param FailureHandlerInterface $failureHandler
     * @param array                   $parameters
     *
     * @return self
     */
    public function failureHandler(FailureHandlerInterface $failureHandler, array $parameters = []): self
    {
        $this->failureHandler = $failureHandler;
        $this->failureParameters = $parameters;

        return $this;
    }

    /**
     * ExceptionHandlerInterface you want to setup
     *
     * @param ExceptionHandlerInterface $exceptionHandler
     *
     * @return self
     */
    public function exceptionHandler(ExceptionHandlerInterface $exceptionHandler): self
    {
        $this->exceptionHandler = $exceptionHandler;

        return $this;
    }

    /**
     * PreprocessorInterface you want to setup
     *
     * @param PreprocessorInterface $preprocessor
     * @param array                 $parameters
     *
     * @return self
     */
    public function preprocessor(PreprocessorInterface $preprocessor, array $parameters = []): self
    {
        $this->preprocessor = $preprocessor;
        $this->preprocessorParameters = $parameters;

        return $this;
    }

    /**
     * ValidatorInterface you want to setup
     *
     * @param ValidatorInterface $validator
     *
     * @return self
     */
    public function validator(ValidatorInterface $validator): self
    {
        $this->customValidator = $validator;

        return $this;
    }

    /**
     * Handle the form processing
     *
     * @param string $formContainerId The form service name in the container
     * @param object $entity          The entity (doctrine entity, some model, whatever the form uses)
     * @param array  $data            The data to parse
     *
     * @return mixed The result produced either by the success handle or by the failure handler
     * @throws Exception
     */
    public function handle(string $formContainerId, $entity, array $data)
    {
        $form = $this->formFactory->create($formContainerId, $entity);

        if ($this->preprocessor !== null) {
            $data = $this->preprocessor->preprocess($data, $this->preprocessorParameters);
        }

        $customValidation = ($this->customValidator !== null) ? $this->customValidator->validate($data) : true;

        $form->submit($data);
        $formValidation = $form->isValid();

        try {
            if ($formValidation && $customValidation) {
                $result = $this->successHandler->onSuccess($form->getData(), $this->succesParameters);
            } else {
                $customErrors = ($this->customValidator !== null) ? $this->customValidator->getErrors() : [];

                $result = $this->failureHandler->onFailure($form->getErrors(true), $customErrors, $this->failureParameters);
            }
        } catch (Exception $exception) {
            if ($this->exceptionHandler !== null) {
                $result = $this->exceptionHandler->onException($exception);
            } else {
                throw $exception;
            }

        }

        return $result;
    }
}
