<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\ControllerExceptionHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\ControllerFailureHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\FormHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\NoOperationHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class ControllerFormHandlerTest.
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Valentin - Chaplean <valentin@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (https://www.chaplean.coop)
 */
class ControllerFormHandlerTest extends MockeryTestCase
{
    /**
     * @var ContainerInterface|\Mockery\MockInterface
     */
    private $containerMock;

    /**
     * @var FormHandler|\Mockery\MockInterface
     */
    private $formHandler;

    /**
     * @var ViewHandlerInterface|\Mockery\MockInterface
     */
    private $viewHandlerMock;

    /**
     * @var ControllerSuccessHandler|\Mockery\MockInterface
     */
    private $successMock;

    /**
     * @var ControllerFailureHandler|\Mockery\MockInterface
     */
    private $failureMock;

    /**
     * @var ControllerExceptionHandler|\Mockery\MockInterface
     */
    private $exceptionMock;

    /**
     * @var ValidatorInterface|\Mockery\MockInterface
     */
    private $validatorMock;

    /**
     * @var PreprocessorInterface|\Mockery\MockInterface
     */
    private $preprocessorMock;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $this->containerMock = \Mockery::mock(ContainerInterface::class);
        $this->formHandler = \Mockery::mock(FormHandler::class);
        $this->viewHandlerMock = new ViewHandler(
            \Mockery::mock('Symfony\Component\Routing\RouterInterface'),
            \Mockery::mock('FOS\RestBundle\Serializer\Serializer'),
            \Mockery::mock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface'),
            new RequestStack(),
            ['json' => false]
        );

        $this->viewHandlerMock->registerHandler(
            'json',
            function (ViewHandler $viewHandler, View $view, Request $request, string $format) use ($serializer) {
                return new Response($serializer->serialize($view->getData(), 'json'), $view->getStatusCode() ?? 200);
            }
        );

        $this->successMock = \Mockery::mock(ControllerSuccessHandler::class);
        $this->failureMock = \Mockery::mock(ControllerFailureHandler::class);
        $this->exceptionMock = \Mockery::mock(ControllerExceptionHandler::class);
        $this->validatorMock = \Mockery::mock(ValidatorInterface::class);
        $this->preprocessorMock = \Mockery::mock(PreprocessorInterface::class);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::__construct
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::successHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::preprocessor()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::validator()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @return void
     */
    public function testHandleSuccessCallsHandlers()
    {
        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $dummy2 = new DummyEntity();

        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class, FailureHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);
        $this->containerMock->shouldReceive('get')->times(4)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $this->successMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->failureMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->exceptionMock->shouldReceive('setHandler')->once()->with(null)->andReturnSelf();

        $this->formHandler->shouldReceive('successHandler')->once()->withArgs([$this->successMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('failureHandler')->once()->withArgs([$this->failureMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('exceptionHandler')->once()->withArgs([$this->exceptionMock])->andReturnSelf();
        $this->formHandler->shouldReceive('preprocessor')->once()->withArgs([$mockHandler, []])->andReturnSelf();
        $this->formHandler->shouldReceive('validator')->once()->with($mockHandler)->andReturnSelf();

        $this->formHandler->shouldReceive('handle')->once()->withArgs([DummyEntityType::class, $dummy2, $request->request->all()])->andReturn(View::create($dummy));

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $response = $formHandler
            ->successHandler('test.mock.success')
            ->preprocessor('fake.preprocessor')
            ->validator('fake.validator')
            ->handle(DummyEntityType::class, $dummy2, $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::failureHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @return void
     */
    public function testHandleFailureCallsHandlers()
    {
        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class, FailureHandlerInterface::class);
        $this->containerMock->shouldReceive('get')->times(2)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $this->successMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->failureMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->exceptionMock->shouldReceive('setHandler')->once()->with(null)->andReturnSelf();

        $this->formHandler->shouldReceive('successHandler')->once()->withArgs([$this->successMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('failureHandler')->once()->withArgs([$this->failureMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('exceptionHandler')->once()->withArgs([$this->exceptionMock])->andReturnSelf();

        $this->formHandler->shouldReceive('handle')->once()->andReturn(View::create(['some return value' => 42], 400));

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $response = $formHandler
            ->failureHandler('test.mock.failure')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"some return value":42}', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @return void
     */
    public function testHandleSuccessWithDefaults()
    {
        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class, FailureHandlerInterface::class);
        $this->containerMock->shouldReceive('get')->times(2)->andReturn($mockHandler);

        $this->successMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->failureMock->shouldReceive('setHandler')->once()->with($mockHandler)->andReturnSelf();
        $this->exceptionMock->shouldReceive('setHandler')->once()->with(null)->andReturnSelf();

        $this->formHandler->shouldReceive('successHandler')->once()->withArgs([$this->successMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('failureHandler')->once()->withArgs([$this->failureMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('exceptionHandler')->once()->withArgs([$this->exceptionMock])->andReturnSelf();

        $this->formHandler->shouldReceive('handle')->once()->andReturn(View::create($dummy));

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $response = $formHandler->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @group t33209
     *
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @return void
     */
    public function testHandleSuccessWithDefaultsWithHandlerInjection()
    {
        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $successHandlerInstance = new NoOperationHandler();
        $failureHandlerinstance = new NoOperationHandler();

        $this->successMock->shouldReceive('setGroups')->once()->with([])->andReturnSelf();
        $this->successMock->shouldReceive('setHandler')->once()->with($successHandlerInstance)->andReturnSelf();
        $this->failureMock->shouldReceive('setHandler')->once()->with($failureHandlerinstance)->andReturnSelf();
        $this->exceptionMock->shouldReceive('setHandler')->once()->with(null)->andReturnSelf();

        $this->formHandler->shouldReceive('successHandler')->once()->withArgs([$this->successMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('failureHandler')->once()->withArgs([$this->failureMock, []])->andReturnSelf();
        $this->formHandler->shouldReceive('exceptionHandler')->once()->withArgs([$this->exceptionMock])->andReturnSelf();

        $this->formHandler->shouldReceive('handle')->once()->andReturn(View::create($dummy));

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $response = $formHandler
            ->successHandler($successHandlerInstance)
            ->failureHandler($failureHandlerinstance)
            ->setGroups([])
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
//     *
//     * @return void
//     */
//    public function testHandleFailureWithDefaults()
//    {
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($this->successMock, $this->failureMock);
//
//        $this->failureMock->shouldReceive('onFailure')
//            ->once()
//            ->andReturn();
//
//        $request = new Request();
//        $request->setRequestFormat('json');
//
//        $formHandler = new ControllerFormHandler(
//            $this->containerMock,
//            $this->formHandler,
//            $this->successMock,
//            $this->failureMock,
//            $this->exceptionMock,
//            $this->viewHandlerMock
//        );
//
//        $response = $formHandler
//            ->handle(DummyEntityType::class, new DummyEntity(), $request);
//
//        $this->assertEquals('{"dummy_entity_form_type[name]":"not blank"}', $response->getContent());
//        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
//    }
//
    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::setGroups
     *
     * @return void
     */
    public function testHandleSuccessGroups()
    {
        $dummy = new DummyEntity();
        $dummy->setId(2);
        $dummy->setName('something');

        $this->successMock->shouldReceive('setGroups')->with(['group']);

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $formHandler->setGroups(['group']);
    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
//     *
//     * @return void
//     */
//    public function testHandleSuccessPreprocess()
//    {
//        $dummy = new DummyEntity();
//        $dummy->setId(3);
//        $dummy->setName('something');
//
//        $this->formMock->shouldReceive('isValid')
//            ->once()
//            ->andReturnTrue();
//
//        $this->successMock->shouldReceive('onSuccess')
//            ->once()
//            ->andReturn($dummy);
//
//        $preprocessMock = \Mockery::mock(PreprocessorInterface::class);
//        $preprocessMock->shouldReceive('preprocess')
//            ->once()
//            ->andReturn(['name' => 'something']);
//
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($this->successMock, $this->failureMock, $preprocessMock);
//
//        $request = new Request();
//        $request->setRequestFormat('json');
//
//        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//
//        $response = $formHandler
//            ->preprocessor('test.mock.preprocessor')
//            ->handle(DummyEntityType::class, new DummyEntity(), $request);
//
//        $this->assertEquals('{"id":3,"name":"something"}', $response->getContent());
//        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
//    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
//     *
//     * @return void
//     */
//    public function testHandleValidatorFails()
//    {
//        $this->failureMock->shouldReceive('onFailure')
//            ->once()
//            ->andReturn(['some error']);
//
//        $this->validatorMock->shouldReceive('validate')
//            ->once()
//            ->andReturn(false);
//
//        $this->validatorMock->shouldReceive('getErrors')
//            ->once()
//            ->andReturn([]);
//
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($this->successMock, $this->failureMock, $this->validatorMock);
//
//        $request = new Request(
//            [],
//            ['name' => 'something']
//        );
//        $request->setRequestFormat('json');
//
//        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//
//        $response = $formHandler
//            ->validator('test.mock.validator')
//            ->handle(DummyEntityType::class, new DummyEntity(), $request);
//
//        $this->assertEquals('["some error"]', $response->getContent());
//        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
//    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::constructHandlers
//     *
//     * @return void
//     */
//    public function testConstructHandlersDefaultSuccessFailureHandlers()
//    {
//        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PersisterSuccessHandler::class);
//        $failureMock = \Mockery::mock(AngularFailureHandler::class, FailureHandlerInterface::class);
//
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($successMock, $failureMock);
//
//        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//
//        $successHandler = $this->getNotPublicProperty(FormHandler::class, 'successHandler');
//        $failureHandler = $this->getNotPublicProperty(FormHandler::class, 'failureHandler');
//
//        $this->assertNull($successHandler->getValue($formHandler));
//        $this->assertNull($failureHandler->getValue($formHandler));
//
//        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
//        $constructHandlers->invoke($formHandler);
//
//        $this->assertInstanceOf(PersisterSuccessHandler::class, $successHandler->getValue($formHandler));
//        $this->assertInstanceOf(SuccessHandlerInterface::class, $successHandler->getValue($formHandler));
//        $this->assertInstanceOf(AngularFailureHandler::class, $failureHandler->getValue($formHandler));
//        $this->assertInstanceOf(FailureHandlerInterface::class, $failureHandler->getValue($formHandler));
//    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::constructHandlers
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::preprocessor
//     *
//     * @return void
//     */
//    public function testConstructHandlersGivenPreprocessor()
//    {
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($this->successMock, $this->failureMock, $this->preprocessorMock);
//
//        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//        $handler->preprocessor('test.mock.preprocessor');
//
//        $preprocessor = $this->getNotPublicProperty(FormHandler::class, 'preprocessor');
//        $this->assertNull($preprocessor->getValue($handler));
//
//        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
//        $constructHandlers->invoke($handler);
//
//        $this->assertInstanceOf(PreprocessorInterface::class, $preprocessor->getValue($handler));
//    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::constructHandlers
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::validator
//     *
//     * @return void
//     */
//    public function testConstructHandlersGivenValidator()
//    {
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($this->successMock, $this->failureMock, $this->validatorMock);
//
//        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//        $handler->validator('test.mock.validator');
//
//        $validator = $this->getNotPublicProperty(FormHandler::class, 'customValidator');
//        $this->assertNull($validator->getValue($handler));
//
//        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
//        $constructHandlers->invoke($handler);
//
//        $this->assertInstanceOf(ValidatorInterface::class, $validator->getValue($handler));
//    }
//
//    /**
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::constructHandlers
//     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::validator
//     *
//     * @return void
//     */
//    public function testConstructHandlersPreprocessorDefaultsToSuccessHandler()
//    {
//        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);
//
//        $this->containerMock->shouldReceive('get')
//            ->andReturn($successMock, $this->failureMock);
//
//        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
//        $handler->successHandler('test.mock.success');
//
//        $preprocessor = $this->getNotPublicProperty(FormHandler::class, 'preprocessor');
//        $this->assertNull($preprocessor->getValue($handler));
//
//        $validator = $this->getNotPublicProperty(FormHandler::class, 'customValidator');
//        $this->assertNull($validator->getValue($handler));
//
//        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
//        $constructHandlers->invoke($handler);
//
//        $this->assertInstanceOf(PreprocessorInterface::class, $preprocessor->getValue($handler));
//        $this->assertInstanceOf(ValidatorInterface::class, $validator->getValue($handler));
//    }
//
    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.success' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonSuccessHandlerFails()
    {
        $mockHandler = \Mockery::mock(FailureHandlerInterface::class);

        $this->containerMock->shouldReceive('get')->times(1)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $formHandler
            ->successHandler('test.mock.success')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.failure' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonFailureHandlerFails()
    {
        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class);

        $this->containerMock->shouldReceive('get')->times(2)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $formHandler
            ->failureHandler('test.mock.failure')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.preprocessor' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonPreprocessorFails()
    {
        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class, FailureHandlerInterface::class);

        $this->containerMock->shouldReceive('get')->times(3)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $formHandler
            ->preprocessor('test.mock.preprocessor')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::handle()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerFormHandler::getHandler()
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.validator' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonValidatorFails()
    {
        $mockHandler = \Mockery::mock(SuccessHandlerInterface::class, FailureHandlerInterface::class);

        $this->containerMock->shouldReceive('get')->times(3)->andReturn($mockHandler);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new ControllerFormHandler(
            $this->containerMock,
            $this->formHandler,
            $this->successMock,
            $this->failureMock,
            $this->exceptionMock,
            $this->viewHandlerMock
        );

        $formHandler
            ->validator('test.mock.validator')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);
    }
}
