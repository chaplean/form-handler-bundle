<?php

use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Class PersisterSuccessHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (https://www.chaplean.coop)
 * @since     1.0.0
 */
class PersisterSuccessHandlerTest extends MockeryTestCase
{
    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler::__construct()
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler::onSuccess()
     *
     * @return void
     */
    public function testOnSuccess()
    {
        $em = \Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('persist')->once();

        $handler = new PersisterSuccessHandler($em);

        $dummy = new DummyEntity();
        $dummy->setName('test');

        $dummyEntity = $handler->onSuccess($dummy, []);

        $this->assertEquals($dummy, $dummyEntity);
    }
}
