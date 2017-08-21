<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\AngularFailureHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\FormHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\ChapleanUnitTrait;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class FormHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class FormHandlerTest extends TypeTestCase
{
    use ChapleanUnitTrait;

    /**
     * @var ContainerInterface|\Mockery\MockInterface
     */
    private $containerMock;

    /**
     * @var RegistryInterface|\Mockery\MockInterface
     */
    private $registryMock;

    /**
     * @var ViewHandlerInterface|\Mockery\MockInterface
     */
    private $viewHandlerMock;

    /**
     * @var SuccessHandlerInterface|\Mockery\MockInterface
     */
    private $successMock;

    /**
     * @var FailureHandlerInterface|\Mockery\MockInterface
     */
    private $failureMock;

    /**
     * @var ValidatorInterface|\Mockery\MockInterface
     */
    private $validatorMock;

    /**
     * @var PreprocessorInterface|\Mockery\MockInterface
     */
    private $preprocessorMock;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface|\Mockery\MockInterface
     */
    private $formFactoryMock;

    /**
     * @var FormInterface|\Mockery\MockInterface
     */
    private $formMock;

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
        $this->registryMock = \Mockery::mock(RegistryInterface::class);
        $this->viewHandlerMock = new ViewHandler(
            \Mockery::mock('Symfony\Component\Routing\RouterInterface'),
            \Mockery::mock('FOS\RestBundle\Serializer\Serializer'),
            \Mockery::mock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface'),
            new RequestStack(),
            ['json' => false]
        );

        $this->viewHandlerMock->registerHandler(
            'json',
            function (ViewHandler $viewHandler, View $view, Request $request, string $format) use($serializer) {
                return new Response($serializer->serialize($view->getData(), 'json'), $view->getStatusCode() ?? 200);
            }
        );

        $em = \Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('flush')
            ->once();

        $this->registryMock->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $this->successMock = \Mockery::mock(SuccessHandlerInterface::class);
        $this->failureMock = \Mockery::mock(FailureHandlerInterface::class);
        $this->validatorMock = \Mockery::mock(ValidatorInterface::class);
        $this->preprocessorMock = \Mockery::mock(PreprocessorInterface::class);
        $this->formFactoryMock = \Mockery::mock(FormFactoryInterface::class);
        $this->formMock = \Mockery::mock(FormInterface::class);

        $this->formFactoryMock->shouldReceive('create')
            ->andReturn($this->formMock);
        $this->formMock->shouldReceive('submit')
            ->andReturn(null);

        $this->formMock->shouldReceive('isValid')
            ->once()
            ->andReturnFalse()
            ->byDefault();

        $this->formMock->shouldReceive('getData', 'getErrors')
            ->andReturn(new FormErrorIterator($this->formMock, []))
            ->byDefault();
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleSuccessCallsHandlers()
    {
        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $this->formMock->shouldReceive('isValid')
            ->once()
            ->andReturnTrue();

        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);
        $successMock->shouldReceive('onSuccess')
            ->once()
            ->andReturn($dummy);
        $successMock->shouldReceive('preprocess')
            ->once()
            ->andReturn(['name' => 'something']);
        $successMock->shouldReceive('validate')
            ->once()
            ->andReturn(true);

        $this->containerMock->shouldReceive('get')
            ->andReturn($successMock, $this->failureMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->successHandler('test.mock.success')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleFailureCallsHandlers()
    {
        $this->failureMock->shouldReceive('onFailure')
            ->once()
            ->andReturn(['some return value' => 42]);

        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->failureHandler('test.mock.failure')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"some return value":42}', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleSuccessWithDefaults()
    {
        $dummy = new DummyEntity();
        $dummy->setId(1);
        $dummy->setName('something');

        $this->formMock->shouldReceive('isValid')
            ->once()
            ->andReturnTrue();

        $this->successMock->shouldReceive('onSuccess')
            ->once()
            ->andReturn($dummy);

        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":1,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleFailureWithDefaults()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);

        $this->failureMock->shouldReceive('onFailure')
            ->once()
            ->andReturn(['dummy_entity_form_type[name]' => 'not blank']);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"dummy_entity_form_type[name]":"not blank"}', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::setGroups
     *
     * @return void
     */
    public function testHandleSuccessGroups()
    {
        $dummy = new DummyEntity();
        $dummy->setId(2);
        $dummy->setName('something');

        $this->formMock->shouldReceive('isValid')
            ->once()
            ->andReturnTrue();

        $this->successMock->shouldReceive('onSuccess')
            ->once()
            ->andReturn($dummy);

        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->setGroups(['dummy_entity_name'])
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":2,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleSuccessPreprocess()
    {
        $dummy = new DummyEntity();
        $dummy->setId(3);
        $dummy->setName('something');

        $this->formMock->shouldReceive('isValid')
            ->once()
            ->andReturnTrue();

        $this->successMock->shouldReceive('onSuccess')
            ->once()
            ->andReturn($dummy);

        $preprocessMock = \Mockery::mock(PreprocessorInterface::class);
        $preprocessMock->shouldReceive('preprocess')
            ->once()
            ->andReturn(['name' => 'something']);

        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock, $preprocessMock);

        $request = new Request();
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->preprocessor('test.mock.preprocessor')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('{"id":3,"name":"something"}', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle
     *
     * @return void
     */
    public function testHandleValidatorFails()
    {
        $this->failureMock->shouldReceive('onFailure')
            ->once()
            ->andReturn(['some error']);

        $this->validatorMock->shouldReceive('validate')
            ->once()
            ->andReturn(false);

        $this->validatorMock->shouldReceive('getErrors')
            ->once()
            ->andReturn([]);

        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock, $this->validatorMock);

        $request = new Request(
            [],
            ['name' => 'something']
        );
        $request->setRequestFormat('json');

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $response = $formHandler
            ->validator('test.mock.validator')
            ->handle(DummyEntityType::class, new DummyEntity(), $request);

        $this->assertEquals('["some error"]', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     *
     * @return void
     */
    public function testConstructHandlersDefaultSuccessFailureHandlers()
    {
        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PersisterSuccessHandler::class);
        $failureMock = \Mockery::mock(AngularFailureHandler::class, FailureHandlerInterface::class);

        $this->containerMock->shouldReceive('get')
            ->andReturn($successMock, $failureMock);

        $formHandler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);

        $successHandler = $this->getNotPublicProperty(FormHandler::class, 'successHandler');
        $failureHandler = $this->getNotPublicProperty(FormHandler::class, 'failureHandler');

        $this->assertNull($successHandler->getValue($formHandler));
        $this->assertNull($failureHandler->getValue($formHandler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($formHandler);

        $this->assertInstanceOf(PersisterSuccessHandler::class, $successHandler->getValue($formHandler));
        $this->assertInstanceOf(SuccessHandlerInterface::class, $successHandler->getValue($formHandler));
        $this->assertInstanceOf(AngularFailureHandler::class, $failureHandler->getValue($formHandler));
        $this->assertInstanceOf(FailureHandlerInterface::class, $failureHandler->getValue($formHandler));
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::preprocessor
     *
     * @return void
     */
    public function testConstructHandlersGivenPreprocessor()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock, $this->preprocessorMock);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->preprocessor('test.mock.preprocessor');

        $preprocessor = $this->getNotPublicProperty(FormHandler::class, 'preprocessor');
        $this->assertNull($preprocessor->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(PreprocessorInterface::class, $preprocessor->getValue($handler));
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::validator
     *
     * @return void
     */
    public function testConstructHandlersGivenValidator()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock, $this->validatorMock);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->validator('test.mock.validator');

        $validator = $this->getNotPublicProperty(FormHandler::class, 'customValidator');
        $this->assertNull($validator->getValue($handler));

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);

        $this->assertInstanceOf(ValidatorInterface::class, $validator->getValue($handler));
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::validator
     *
     * @return void
     */
    public function testConstructHandlersPreprocessorDefaultsToSuccessHandler()
    {
        $successMock = \Mockery::mock(SuccessHandlerInterface::class, PreprocessorInterface::class, ValidatorInterface::class);

        $this->containerMock->shouldReceive('get')
            ->andReturn($successMock, $this->failureMock);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
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
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::successHandler
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.success' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonSuccessHandlerFails()
    {
        $this->containerMock->shouldReceive('get')
            ->withArgs(['test.mock.success'])
            ->andReturn(42);
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->failureMock);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->successHandler('test.mock.success');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::failureHandler
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.failure' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonFailureHandlerFails()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->failureHandler('test.mock.failure');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::preprocessor
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.preprocessor' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonPreprocessorFails()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);
        $this->containerMock->shouldReceive('get')
            ->withArgs(['test.mock.preprocessor'])
            ->andReturn(42);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->preprocessor('test.mock.preprocessor');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }

    /**
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::constructHandlers
     * @covers                    \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::validator
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  'test.mock.validator' is supposed to implement Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface
     *
     * @return void
     */
    public function testConstructHandlersGivenNonValidatorFails()
    {
        $this->containerMock->shouldReceive('get')
            ->andReturn($this->successMock, $this->failureMock);
        $this->containerMock->shouldReceive('get')
            ->withArgs(['test.mock.validator'])
            ->andReturn(42);

        $handler = new FormHandler($this->containerMock, $this->registryMock, $this->formFactoryMock, $this->viewHandlerMock);
        $handler->validator('test.mock.validator');

        $constructHandlers = $this->getNotPublicMethod(FormHandler::class, 'constructHandlers');
        $constructHandlers->invoke($handler);
    }
}
