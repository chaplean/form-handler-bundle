<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\FailureHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\FormHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PreprocessorInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\SuccessHandlerInterface;
use Chaplean\Bundle\FormHandlerBundle\Form\ValidatorInterface;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\ChapleanUnitTrait;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Form\Type\DummyEntityType;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class FormHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class FormHandlerTest extends MockeryTestCase
{
    use ChapleanUnitTrait;

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
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::__construct()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::successHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::preprocessor()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::validator()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle()
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

        $this->successMock->shouldReceive('onSuccess')
            ->once()
            ->andReturn($dummy);
        $this->preprocessorMock->shouldReceive('preprocess')
            ->once()
            ->andReturn(['name' => 'something']);
        $this->validatorMock->shouldReceive('validate')
            ->once()
            ->andReturn(true);

        $formHandler = new FormHandler($this->formFactoryMock);

        $response = $formHandler
            ->successHandler($this->successMock)
            ->preprocessor($this->preprocessorMock)
            ->validator($this->validatorMock)
            ->handle(DummyEntityType::class, new DummyEntity(), []);

        $this->assertEquals($dummy, $response);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::__construct()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::failureHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle()
     *
     * @return void
     */
    public function testHandleFailureCallsHandlers()
    {
        $this->failureMock->shouldReceive('onFailure')
            ->once()
            ->andReturn(['some return value' => 42]);

        $formHandler = new FormHandler($this->formFactoryMock);

        $response = $formHandler
            ->failureHandler($this->failureMock)
            ->handle(DummyEntityType::class, new DummyEntity(), []);

        $this->assertEquals(['some return value' => 42], $response);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::__construct()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::successHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::preprocessor()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle()
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

        $this->preprocessorMock->shouldReceive('preprocess')
            ->once()
            ->andReturn(['name' => 'something']);

        $formHandler = new FormHandler($this->formFactoryMock);

        $response = $formHandler
            ->preprocessor($this->preprocessorMock)
            ->successHandler($this->successMock)
            ->handle(DummyEntityType::class, new DummyEntity(), []);

        $this->assertEquals($dummy, $response);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::__construct()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::failureHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::validator()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\FormHandler::handle()
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

        $formHandler = new FormHandler($this->formFactoryMock);

        $response = $formHandler
            ->validator($this->validatorMock)
            ->failureHandler($this->failureMock)
            ->handle(DummyEntityType::class, new DummyEntity(), ['name' => 'something']);

        $this->assertEquals(['some error'], $response);
    }
}
