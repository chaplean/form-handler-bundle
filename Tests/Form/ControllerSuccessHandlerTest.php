<?php

namespace Chaplean\Bundle\FormHandlerBundle\Tests\Form;

use Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\NoOperationHandler;
use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ControllerSuccessHandlerTest
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Tests\Form
 * @author    Nicolas - Chaplean <nicolas@chaplean.com>
 * @copyright 2014 - 2019 Chaplean (https://www.chaplean.com)
 */
class ControllerSuccessHandlerTest extends MockeryTestCase
{
    /** @var EntityManagerInterface|MockInterface */
    protected $em;

    /** @var ControllerSuccessHandler */
    protected $handler;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->em = \Mockery::mock(EntityManagerInterface::class);

        $this->handler = new ControllerSuccessHandler($this->em);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::__construct()
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(ControllerSuccessHandler::class, $this->handler);
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setGroups()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::onSuccess()
     *
     * @return void
     */
    public function testOnSuccessNothingToDisplay()
    {
        $entity = new DummyEntity();
        $handlerInstance = new NoOperationHandler();

        $uow = \Mockery::mock(UnitOfWork::class);
        $uow->shouldReceive('isScheduledForInsert')->once()->with(null)->andThrow(new \Exception());

        $this->em->shouldReceive('getUnitOfWork')->once()->andReturn($uow);
        $this->em->shouldReceive('flush')->once();

        $this->handler->setHandler($handlerInstance);
        $this->handler->setGroups(['test']);
        $result = $this->handler->onSuccess($entity, []);

        $this->assertEquals(Response::HTTP_OK, $result->getResponse()->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setGroups()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::onSuccess()
     *
     * @return void
     */
    public function testOnSuccessEntityUpdated()
    {
        $entity = new DummyEntity();
        $handlerInstance = new PersisterSuccessHandler($this->em);

        $uow = \Mockery::mock(UnitOfWork::class);
        $uow->shouldReceive('isScheduledForInsert')->once()->with($entity)->andReturnFalse();

        $this->em->shouldReceive('getUnitOfWork')->once()->andReturn($uow);
        $this->em->shouldReceive('persist')->once()->with($entity);
        $this->em->shouldReceive('flush')->once();

        $this->handler->setHandler($handlerInstance);
        $this->handler->setGroups(['test']);
        $result = $this->handler->onSuccess($entity, []);

        $this->assertEquals(Response::HTTP_OK, $result->getResponse()->getStatusCode());
    }

    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setHandler()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::setGroups()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\ControllerSuccessHandler::onSuccess()
     *
     * @return void
     */
    public function testOnSuccessEntityInserted()
    {
        $entity = new DummyEntity();
        $handlerInstance = new PersisterSuccessHandler($this->em);

        $uow = \Mockery::mock(UnitOfWork::class);
        $uow->shouldReceive('isScheduledForInsert')->once()->with($entity)->andReturnTrue();

        $this->em->shouldReceive('getUnitOfWork')->once()->andReturn($uow);
        $this->em->shouldReceive('persist')->once()->with($entity);
        $this->em->shouldReceive('flush')->once();

        $this->handler->setHandler($handlerInstance);
        $this->handler->setGroups([]);
        $result = $this->handler->onSuccess($entity, []);

        $this->assertEquals(Response::HTTP_CREATED, $result->getResponse()->getStatusCode());
    }
}
