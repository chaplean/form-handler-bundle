<?php

namespace Tests\Chaplean\Bundle\FormHandlerBundle\Form;

use Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity\DummyEntity;
use Chaplean\Bundle\UnitBundle\Test\LogicalTestCase;

/**
 * Class PersisterSuccessHandlerTest
 *
 * @package   Tests\Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 *
 * @coversDefaultClass Chaplean\Bundle\FormHandlerBundle\Form\PersisterSuccessHandler
 */
class PersisterSuccessHandlerTest extends LogicalTestCase
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
        $this->getContainer()->get('chaplean_form_handler.success_handler.persister');
    }

    /**
     * @covers ::onSuccess
     *
     * @return void
     */
    public function testOnSuccess()
    {
        $handler = $this->getContainer()->get('chaplean_form_handler.success_handler.persister');

        $repo = $this->em->getRepository(DummyEntity::class);
        $this->assertEmpty($repo->findAll());

        $dummy = new DummyEntity();
        $dummy->setName('test');

        $dummyEntity = $handler->onSuccess($dummy);
        $this->em->flush();

        $this->assertCount(1, $repo->findAll());
    }
}
