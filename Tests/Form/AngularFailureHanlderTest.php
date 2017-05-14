<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityContainerType;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\EntityDummyEntityType;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\ParentModelType;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Model\DummyEntityContainer;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Model\ParentModel;
use Chaplean\Bundle\UnitBundle\Test\LogicalTestCase;
use Symfony\Component\Form\FormFactory;

/**
 * Class AngularFailureHanlderTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 *
 * @coversDefaultClass Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler
 */
class AngularFailureHanlderTest extends LogicalTestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$withDefaultData = false;
        self::$datafixturesEnabled = false;

        parent::setUpBeforeClass();
    }

    /**
     * @covers ::onFailure
     *
     * @return void
     */
    public function testOnFailure()
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->getContainer()->get('form.factory');
        $form = $formFactory->create(DummyEntityType::class, new DummyEntity());
        $form->submit(['extra_thing' => 'that shouldnt be there']);

        $this->assertFalse($form->isValid());

        $handler = $this->getContainer()->get('chaplean_form_handler.failure_handler.angular');
        $errorToArray = $this->getNotPublicMethod(AngularFailureHandler::class, 'onFailure');
        $errors = $errorToArray->invoke($handler, $form->getErrors(true), []);

        $this->assertEquals(
            [
                'dummy_entity_form_type' => [
                    'Ce formulaire ne doit pas contenir des champs supplémentaires. (extra_thing)'
                ],
                'dummy_entity_form_type[name]' => 'not blank'
            ],
            $errors
        );
    }

    /**
     * @covers ::errorToArray
     *
     * @return void
     */
    public function testErrorToArray()
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->getContainer()->get('form.factory');
        $form = $formFactory->create(DummyEntityType::class, new DummyEntity());
        $form->submit(['name' => 3.14, 'extra_thing' => 'that shouldnt be there']);

        $this->assertFalse($form->isValid());

        $errorToArray = $this->getNotPublicMethod(AngularFailureHandler::class, 'errorToArray');
        $errors = $errorToArray->invoke(AngularFailureHandler::class, [], $form->getErrors(true)[0], []);

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
     * @covers ::errorToArray
     *
     * @return void
     */
    public function testErrorToArrayNestedForm()
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->getContainer()->get('form.factory');
        $form = $formFactory->create(ParentModelType::class, new ParentModel());
        $form->submit(['child' => ['name' => null]]);

        $this->assertFalse($form->isValid());

        $errorToArray = $this->getNotPublicMethod(AngularFailureHandler::class, 'errorToArray');
        $errors = $errorToArray->invoke(AngularFailureHandler::class, [], $form->getErrors(true)[0], []);

        $this->assertEquals(
            [
                'parent_model_form_type[child][name]' => 'not blank'
            ],
            $errors
        );
    }
}
