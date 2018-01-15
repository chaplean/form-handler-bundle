<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\ChapleanUnitTrait;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\ParentModelType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class AngularFailureHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class AngularFailureHandlerTest extends TypeTestCase
{
    use ChapleanUnitTrait;

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler::errorToArray
     *
     * @return void
     */
    public function testErrorToArray()
    {
        $form = $this->factory->create(DummyEntityType::class);

        $error = new FormError('Ce formulaire ne doit pas contenir des champs supplémentaires. (extra_thing)');
        $error->setOrigin($form);

        $errorToArray = $this->getNotPublicMethod(AngularFailureHandler::class, 'errorToArray');
        $errors = $errorToArray->invoke(new AngularFailureHandler(), [], $error, []);

        $this->assertEquals(
            [
                'dummy_entity_form_type' => [
                    'Ce formulaire ne doit pas contenir des champs supplémentaires. (extra_thing)'
                ]
            ],
            $errors
        );
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler::errorToArray
     *
     * @return void
     */
    public function testErrorToArrayNestedForm()
    {
        $formParent = $this->factory->create(ParentModelType::class);
        $formParent->submit(['child' => ['name' => null]]);

        $errorChild = new FormError('not blank');
        $errorChild->setOrigin($formParent->get('child')->get('name'));

        $parentError = $errorChild;

        $errorToArray = $this->getNotPublicMethod(AngularFailureHandler::class, 'errorToArray');
        $errors = $errorToArray->invoke(new AngularFailureHandler(), [], $parentError, []);

        $this->assertEquals(
            [
                'parent_model_form_type[child][name]' => 'not blank'
            ],
            $errors
        );
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler::onFailure
     *
     * @return void
     */
    public function testOnFailure()
    {
        $form = $this->factory->create(DummyEntityType::class);

        $error1 = new FormError('Ce formulaire ne doit pas contenir des champs supplémentaires. (extra_thing)');
        $error1->setOrigin($form);

        $error2 = new FormError('not blank');
        $error2->setOrigin($form->get('name'));

        $errors = [$error1, $error2];

        $formErrorIterator = new FormErrorIterator($form, $errors);

        $handler = new AngularFailureHandler();
        $errors = $handler->onFailure($formErrorIterator, [], []);

        $this->assertEquals(
            [
                'dummy_entity_form_type'       => [
                    'Ce formulaire ne doit pas contenir des champs supplémentaires. (extra_thing)'
                ],
                'dummy_entity_form_type[name]' => 'not blank'
            ],
            $errors
        );
    }
}
