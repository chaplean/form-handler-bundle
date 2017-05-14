<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\FormHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Chaplean\Bundle\UnitBundle\Test\LogicalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 *
 * @coversDefaultClass Chaplean\Bundle\FormHandlerBundle\Form\FormHandler
 */
class FormHandlerTest extends LogicalTestCase
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
     * @covers ::__construct
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->getContainer()->get('chaplean_form_handler.form_handler');
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleSuccessCallsHandlers()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);
        $successMock->shouldReceive('onSuccess')->once()->andReturn($dummy);
        $successMock->shouldReceive('preprocess')->once()->andReturn(['name' => 'something']);
        $successMock->shouldReceive('validate')->once()->andReturn(true);
        $this->getContainer()->set('test.mock.success', $successMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->successHandler('test.mock.success')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(0, $repo->findAll());
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleFailureCallsHandlers()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $failureMock = \Mockery::mock(FailureHandlerInterface::class);
        $failureMock->shouldReceive('onFailure')->once()->andReturn(['some return value' => 42]);
        $this->getContainer()->set('test.mock.failure', $failureMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->failureHandler('test.mock.failure')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"some return value":42}', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertCount(0, $repo->findAll());
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleSuccessWithDefaults()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $repo->findAll());
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleFailureWithDefaults()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $request = new Request();
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"dummy_entity_form_type[name]":"not blank"}', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertCount(0, $repo->findAll());
    }

    /**
     * @covers ::handle
     * @covers ::setGroups
     *
     * @return void
     */
    public function testHandleSuccessGroups()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->setGroups(['dummy_entity_name'])
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $repo->findAll());
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleSuccessPreprocess()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $preprocessMock = \Mockery::mock(PreprocessorInterface::class);
        $preprocessMock->shouldReceive('preprocess')->once()->andReturn(['name' => 'something']);
        $this->getContainer()->set('test.mock.preprocessor', $preprocessMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->preprocessor('test.mock.preprocessor')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":3,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $repo->findAll());
    }

    /**
     * @covers ::handle
     *
     * @return void
     */
    public function testHandleValidatorFails()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $validatorMock = \Mockery::mock(ValidatorInterface::class);
        $validatorMock->shouldReceive('validate')->once()->andReturn(false);
        $validatorMock->shouldReceive('getErrors')->once()->andReturn(['some error']);
        $this->getContainer()->set('test.mock.validator', $validatorMock);

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $response = $this->getContainer()->get('chaplean_form_handler.form_handler')
            ->validator('test.mock.validator')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('["some error"]', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertCount(0, $repo->findAll());
    }

    /**
     * @covers ::constructHandlers
     *
     * @return void
     */
    public function testConstructHandlersDefaultSuccessFailureHandlers()
    {
        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');

        $successHandler = $this->getNotPublicProperty(FormHandler::class, 'successHandler');
        $failureHandler = $this->getNotPublicProperty(FormHandler::class, 'failureHandler');

        $this->assertNull($successHandler->getValue($handler));
        $this->assertNull($failureHandler->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(PersisterSuccessHandler::class, $successHandler->getValue($handler));
        $this->assertInstanceOf(SuccessHandlerInterface::class, $successHandler->getValue($handler));
        $this->assertInstanceOf(AngularFailureHandler::class, $failureHandler->getValue($handler));
        $this->assertInstanceOf(FailureHandlerInterface::class, $failureHandler->getValue($handler));
    }

    /**
     * @covers ::constructHandlers
     * @covers ::preprocessor
     *
     * @return void
     */
    public function testConstructHandlersGivenPreprocessor()
    {
        $preprocessorMock = \Mockery::mock(PreprocessorInterface::class);
        $this->getContainer()->set('test.mock.preprocessor', $preprocessorMock);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->preprocessor('test.mock.preprocessor');

        $preprocessor = $this->getNotPublicProperty(FormHandler::class, 'preprocessor');
        $this->assertNull($preprocessor->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(PreprocessorInterface::class, $preprocessor->getValue($handler));
    }

    /**
     * @covers ::constructHandlers
     * @covers ::validator
     *
     * @return void
     */
    public function testConstructHandlersGivenValidator()
    {
        $validatorMock = \Mockery::mock(ValidatorInterface::class);
        $this->getContainer()->set('test.mock.validator', $validatorMock);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->validator('test.mock.validator');

        $validator = $this->getNotPublicProperty(FormHandler::class, 'customValidator');
        $this->assertNull($validator->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(ValidatorInterface::class, $validator->getValue($handler));
    }

    /**
     * @covers ::constructHandlers
     * @covers ::validator
     *
     * @return void
     */
    public function testConstructHandlersPreprocessorDefaultsToSuccessHandler()
    {
        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);
        $this->getContainer()->set('test.mock.success', $successMock);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->successHandler('test.mock.success');

        $preprocessor = $this->getNotPublicProperty(FormHandler::class, 'preprocessor');
        $this->assertNull($preprocessor->getValue($handler));

        $validator = $this->getNotPublicProperty(FormHandler::class, 'customValidator');
        $this->assertNull($validator->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(PreprocessorInterface::class, $preprocessor->getValue($handler));
        $this->assertInstanceOf(ValidatorInterface::class, $validator->getValue($handler));
    }

    /**
     * @covers ::constructHandlers
     * @covers ::successHandler
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.success' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonSuccessHandlerFails()
    {
        $this->getContainer()->set('test.mock.success', 42);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->successHandler('test.mock.success');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers ::constructHandlers
     * @covers ::failureHandler
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.failure' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonFailureHandlerFails()
    {
        $this->getContainer()->set('test.mock.failure', 42);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->failureHandler('test.mock.failure');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers ::constructHandlers
     * @covers ::preprocessor
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.preprocessor' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonPreprocessorFails()
    {
        $this->getContainer()->set('test.mock.preprocessor', 42);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->preprocessor('test.mock.preprocessor');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers ::constructHandlers
     * @covers ::validator
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.validator' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonValidatorFails()
    {
        $this->getContainer()->set('test.mock.validator', 42);

        $handler = $this->getContainer()->get('chaplean_form_handler.form_handler');
        $handler->validator('test.mock.validator');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }
}
