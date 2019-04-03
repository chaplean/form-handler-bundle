<?php

namespace Chaplean\Bundle\FormHandlerBundle\Tests\Resources;

/**
 * Trait ChapleanUnitTrait.
 *
 * @author    Tom - Chaplean <tom@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (https://www.chaplean.coop)
 * @since     2.0.0
 */
trait ChapleanUnitTrait
{
    /**
     * Data provider to test EmailUtility::IsStatusCodeConfiguredForNotifications
     *
     * @return array
     */
    public function statusCodeAndConfigurationForNotificationChecks()
    {
        return [
            '0 - all enabled'    => [0,   ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],
            '100 - all enabled'  => [100, ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],
            '200 - all enabled'  => [200, ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],
            '302 - all enabled'  => [302, ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],
            '403 - all enabled'  => [403, ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],
            '501 - all enabled'  => [501, ['0', '1XX', '2XX', '3XX', '4XX', '5XX'], true],

            '0 - only ok'        => [0,   ['200'],                                  false],
            '100 - only ok'      => [100, ['200'],                                  false],
            '200 - only ok'      => [200, ['200'],                                  true],
            '302 - only ok'      => [302, ['200'],                                  false],
            '403 - only ok'      => [403, ['200'],                                  false],
            '501 - only ok'      => [501, ['200'],                                  false],

            '0 - only errors'    => [0,   ['0', '4XX', '5XX'],                      true],
            '100 - only errors'  => [100, ['0', '4XX', '5XX'],                      false],
            '200 - only errors'  => [200, ['0', '4XX', '5XX'],                      false],
            '302 - only errors'  => [302, ['0', '4XX', '5XX'],                      false],
            '403 - only errors'  => [403, ['0', '4XX', '5XX'],                      true],
            '501 - only errors'  => [501, ['0', '4XX', '5XX'],                      true],
        ];
    }

    /**
     * Returns a handle to allow calling a private or protected method.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return \ReflectionMethod
     */
    public function getNotPublicMethod($className, $methodName)
    {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Returns a handle allowing access to a private or protected property.
     *
     * @param string $className
     * @param string $propertyName
     *
     * @return \ReflectionProperty
     */
    public function getNotPublicProperty($className, $propertyName)
    {
        $class = new \ReflectionClass($className);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
