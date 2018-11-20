# form-handler-bundle

[![build status](https://git.chaplean.coop/private/bundle/form-handler-bundle/badges/master/build.svg)](https://git.chaplean.coop/private/bundle/form-handler-bundle/commits/master)
[![build status](https://git.chaplean.coop/private/bundle/form-handler-bundle/badges/master/coverage.svg)](https://git.chaplean.coop/private/bundle/form-handler-bundle/commits/master)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/chaplean/form-handler-bundle/issues)



## Table of content

* [Installation](#Installation)
* [Usage](#Usage)
* [Versioning](#Versioning)
* [Contributing](#Contributing)
* [Hacking](#Hacking)
* [License](#License)

## Installation

This bundle requires at least Symfony 3.0.

You can use [composer](https://getcomposer.org) to install form-handler-bundle:
```bash
composer require chaplean/form-handler-bundle
```

Then add to your AppKernel.php:

```php
new Chaplean\Bundle\FormHandlerBundle\ChapleanFormHandlerBundle(),
```

## Usage

Bellow are a few fake controller actions to show off what you can do.

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

## Versioning

form-handler-bundle follows [semantic versioning](https://semver.org/). In short the scheme is MAJOR.MINOR.PATCH where
1. MAJOR is bumped when there is a breaking change,
2. MINOR is bumped when a new feature is added in a backward-compatible way,
3. PATCH is bumped when a bug is fixed in a backward-compatible way.

Versions bellow 1.0.0 are considered experimental and breaking changes may occur at any time.

## Contributing

Contributions are welcomed! There are many ways to contribute, and we appreciate all of them. Here are some of the major ones:

* [Bug Reports](https://github.com/chaplean/form-handler-bundle/issues): While we strive for quality software, bugs can happen and we can't fix issues we're not aware of. So please report even if you're not sure about it or just want to ask a question. If anything the issue might indicate that the documentation can still be improved!
* [Feature Request](https://github.com/chaplean/form-handler-bundle/issues): You have a use case not covered by the current api? Want to suggest a change or add something? We'd be glad to read about it and start a discussion to try to find the best possible solution.
* [Pull Request](https://github.com/chaplean/form-handler-bundle/pulls): Want to contribute code or documentation? We'd love that! If you need help to get started, GitHub as [documentation](https://help.github.com/articles/about-pull-requests/) on pull requests. We use the ["fork and pull model"](https://help.github.com/articles/about-collaborative-development-models/) were contributors push changes to their personnal fork and then create pull requests to the main repository. Please make your pull requests against the `master` branch.

As a reminder, all contributors are expected to follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Hacking

You might find the following commands usefull when hacking on this project:

```bash
# Install dependencies
composer install

# Run tests
bin/phpunit
```

## License

form-handler-bundle is distributed under the terms of the MIT license.

See [LICENSE](LICENSE.md) for details.
