# Installation

## 1. Composer

```
composer require chaplean/form-handler-bundle
```

## 2. AppKernel.php

Add
```
            new Chaplean\Bundle\FormHandlerBundle\ChapleanFormHandlerBundle(),
```

# Usage

Bellow are few fake controller actions to show of what you can do.

```php
<?php

namespace App\Bundle\RestBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Annotations\RouteResource("DummyEntity")
 */
class FakeController extends FOSRestController
{
    /*
     * Those two first actions uses the defaults behaviours of FormHandler.
     * On success it will persist the DummyEntity given to handle.
     * On failure it will convert the form errors to an angular friendly format.
     * No JMS groups will be used when serializing your entity to create the response on success.
     *
     * The first action works with a new DummyEntity.
     * The second action works with an existing DummyEntity.
     */

    public function postAction(Request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    public function putAction(Request $request, DummyEntity $dummy)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->handle(DummyEntityType::class, $dummy, $request);
    }

    /*
     * This action defines groups to use to serialize the DummyEntity to create the response on success.
     */
    public function postWithGroupsAction(Request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->setGroups(['dummy_entity_name'])
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /*
     * The SuccessHandler is the logic that's run when the form submission is valid.
     * The default behaviour is to persist the entity.
     * With the successHandler function you can override this default behaviour by giving an
     * alternative implementation of SuccessHandlerInterface.
     * 
     * successHandler takes a string that's the name of the class implementing SuccessHandlerInterface
     * you registered in the container under.
     * 
     * If the class you gave happens to also implement PreprocessorInterface and / or ValidatorInterface
     * and you haven't called the preprocessor and / or validator methods, your class will be used
     * as the preprocessor and / or validator.
     */
    public function postWithCustomSuccessHandler(request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->successHandler('your_bundle.form.success_handler.do_something_on_valid_form')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /*
     * The FailureHandler is the logic that's run when the form submission is invalid.
     * The default behaviour is to transform the form errors into an angular friendly format.
     * With the failureHandler function you can override this default behaviour by giving an
     * alternative implementation of FailureHandlerInterface.
     * 
     * failureHandler takes a string that's the name of the class implementing FailureHandlerInterface
     * you registered in the container under.
     */
    public function postWithCustomFailureHandler(request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->failureHandler('your_bundle.form.failure_handler.do_something_on_invalid_form')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /*
     * The Preprocessor is the logic that's run before the form validation logic is run.
     * The goal of this is to allow you to run custom transformation (like maybe some filters, or coversions)
     * on the data to submit before it is submited.
     * With the preprocessor function you can define the behaviour you want by giving an
     * implementation of PreprocessorInterface.
     * 
     * preprocessor takes a string that's the name of the class implementing PreprocessorInterface
     * you registered in the container under.
     */
    public function postWithCustomPreprocessor(request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->preprocessor('your_bundle.form.preprocessor.do_custom_preprocessing_on_data')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /*
     * The Validator allows you to define custom validation logic outside of symfony form validator.
     * With the preprocessor function you can define the behaviour you want by giving an
     * implementation of ValidatorInterface.
     * 
     * preprocessor takes a string that's the name of the class implementing ValidatorInterface
     * you registered in the container under.
     */
    public function postWithCustomValidator(request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->validator('your_bundle.form.validator.do_custom_data_validation')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /*
     * This last action uses the whole api capabilities just for the sake of showing it off.
     */
    public function postWithFullApiUsageAction(request $request)
    {
        return $this
            ->get('chaplean_form_handler.form_handler')
            ->successHandler('your_bundle.form.success_handler.do_something_on_valid_form')
            ->failureHandler('your_bundle.form.failure_handler.do_something_on_invalid_form')
            ->preprocessor('your_bundle.form.preprocessor.do_custom_preprocessing_on_data')
            ->validator('your_bundle.form.validator.do_custom_data_validation')
            ->setGroups(['dummy_entity_name'])
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }
}
```
