<?php

use Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler;
use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PersisterSuccessHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class PersisterSuccessHandlerTest extends MockeryTestCase
{
    /**
     * @covers \Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler::onSuccess
     *
     * @return void
     */
    public function testOnSuccess()
    {
        $em = \Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('persist')
            ->once();

        $registry = \Mockery::mock(RegistryInterface::class);
        $registry->shouldReceive('getManager')
            ->andReturn($em);

        $handler = new PersisterSuccessHandler($registry);

        $dummy = new DummyEntity();
        $dummy->setName('test');

        $dummyEntity = $handler->onSuccess($dummy, []);

        $this->assertEquals($dummy, $dummyEntity);
    }
}
