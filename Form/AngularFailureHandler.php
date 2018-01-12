<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;

/**
 * Class AngularFailureHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 *
 * Transforms the form validation errors to be serialized in a Angular friendly way
 */
class AngularFailureHandler implements FailureHandlerInterface
{
    /**
     * Format a FormError and add the result to a list of errors
     *
     * @param array     $errors
     * @param FormError $error
     *
     * @return array
     */
    protected static function errorToArray(array $errors, FormError $error)
    {
        $key = '';
        $origin = $error->getOrigin();
        $message = $error->getMessage();

        while ($origin->getParent() !== null) {
            $key = sprintf('[%s]', $origin->getName()) . $key;
            $origin = $origin->getParent();
        }

        $completeKey = $origin->getName() . $key;

        $messagesParameters = $error->getMessageParameters();
        if (array_key_exists('{{ extra_fields }}', $messagesParameters)) {
            $message .= ' (' . implode(', ', $messagesParameters) . ')';
        }

        if ($completeKey === $origin->getName()) {
            $errors[$completeKey][] = $message;
        } else {
            $errors[$completeKey] = $message;
        }

        return $errors;
    }

    /**
     * Transform the given errors to be used by angular
     *
     * @param FormErrorIterator $formErrors
     * @param array             $customErrors
     * @param array             $parameters
     *
     * @return mixed Data used to build the response to the user
     */
    public function onFailure(FormErrorIterator $formErrors, array $customErrors, array $parameters)
    {
        $errorsAngular = $customErrors;

        /** @var FormError $error */
        foreach ($formErrors as $error) {
            $errorsAngular = self::errorToArray($errorsAngular, $error);
        }

        return $errorsAngular;
    }
}
