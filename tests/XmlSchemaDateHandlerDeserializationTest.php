<?php
namespace GoetasWebservices\Xsd\XsdToPhpRuntime\Tests\Jms\Handler;

use Doctrine\Common\Annotations\AnnotationReader;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Accessor\DefaultAccessorStrategy;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\XmlDeserializationVisitor;
use Metadata\MetadataFactory;

class XmlSchemaDateHandlerDeserializationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlSchemaDateHandler
     */
    protected $handler;
    /**
     * @var DeserializationContext
     */
    protected $context;

    /**
     * @var XmlDeserializationVisitor
     */
    protected $visitor;

    public function setUp()
    {
        $this->handler = new XmlSchemaDateHandler();
        $this->context = DeserializationContext::create();
        $naming = new IdenticalPropertyNamingStrategy();



        $dispatcher = new EventDispatcher();
        $handlerRegistry= new HandlerRegistry();
        $cons = new UnserializeObjectConstructor();

        $navigator = class_exists('JMS\Serializer\GraphNavigator\DeserializationGraphNavigator')
            ? $this->initJmsv2($naming, $handlerRegistry, $cons, $dispatcher)
            : $this->initJmsv1($naming, $handlerRegistry, $cons, $dispatcher)
        ;
        $this->visitor->setNavigator($navigator);
    }

    private function initJmsv2($naming, $handlerRegistry, $cons, $dispatcher)
    {
        $accessor = new DefaultAccessorStrategy();
        $this->visitor = new XmlDeserializationVisitor();
        $metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader(), $naming));
        return new GraphNavigator\DeserializationGraphNavigator($metadataFactory, $handlerRegistry, $cons, $accessor, $dispatcher);
    }

    private function initJmsv1($naming, $handlerRegistry, $cons, $dispatcher)
    {
        $this->visitor = new XmlDeserializationVisitor($naming);
        $metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));
        return new GraphNavigator($metadataFactory, $handlerRegistry, $cons, $dispatcher);
    }

    /**
     * @dataProvider getDeserializeDate
     * @param string $date
     * @param \DateTime $expected
     */
    public function testDeserializeDate($date, \DateTime $expected)
    {
        $element = new \SimpleXMLElement("<Date>$date</Date>");
        $deserialized = $this->handler->deserializeDate($this->visitor, $element, [], $this->context);
        $this->assertEquals($expected, $deserialized);
    }

    public function getDeserializeDate()
    {
        return [
            ['2015-01-01', new \DateTime('2015-01-01')],
            ['2015-01-01Z', new \DateTime('2015-01-01', new \DateTimeZone("UTC"))],
            ['2015-01-01+06:00', new \DateTime('2015-01-01', new \DateTimeZone("+06:00"))],
            ['2015-01-01-20:00', new \DateTime('2015-01-01', new \DateTimeZone("-20:00"))],
        ];
    }

    /**
     * @requires PHP 7.0
     * @dataProvider getDeserializeDateWithTimezone
     * @param string $date
     * @param \DateTime $expected
     * @param string $defaultTimezone
     * @param string $serializeTimezone
     * @param string $deserializeTimezone
     */
    public function testDeserializeDateWithTimezone(
        $date,
        \DateTime $expected,
        $defaultTimezone = 'UTC',
        $serializeTimezone = null,
        $deserializeTimezone = null
    ) {
        $handler = new XmlSchemaDateHandler($defaultTimezone, $serializeTimezone, $deserializeTimezone);
        $element = new \SimpleXMLElement("<Date>$date</Date>");
        $deserialized = $handler->deserializeDate($this->visitor, $element, [], $this->context);
        $this->assertEquals($expected, $deserialized);
    }

    public function getDeserializeDateWithTimezone()
    {
        $tzPlus6 = new \DateTimeZone('+06:00');
        $tzMinus20 = new \DateTimeZone('-20:00');

        return [
            // timezone is not set (default timezone is used)
            ['2015-01-01', new \DateTime('2015-01-01'), 'UTC'],
            ['2015-01-01', new \DateTime('2015-01-01', $tzPlus6), '+06:00'],
            ['2015-01-01', new \DateTime('2015-01-01', $tzMinus20), '-20:00'],
            // timezone is set (note that default timezone is ignored by php DateTime constructor)
            ['2015-01-01Z', new \DateTime('2015-01-01+00:00'), 'UTC'],
            ['2015-01-01Z', new \DateTime('2015-01-01+00:00'), '+06:00'],
            ['2015-01-01+06:00', new \DateTime('2015-01-01+06:00'), 'UTC'],
            ['2015-01-01+06:00', new \DateTime('2015-01-01+06:00'), '-20:00'],
            // deserialize timezone is set (here we expect timezone shifting to required one)
            ['2015-01-01Z', new \DateTime('2015-01-01+00:00'), 'UTC', null, 'UTC'],
            ['2015-01-01Z', new \DateTime('2015-01-01 06:00:00+06:00'), 'UTC', null, '+06:00'],
            ['2015-01-01Z', new \DateTime('2014-12-31 04:00:00-20:00'), 'UTC', null, '-20:00'],
            ['2015-01-01+06:00', new \DateTime('2015-01-01+06:00'), 'UTC', null, '+0:00'],
            ['2015-01-01+06:00', new \DateTime('2015-01-01 02:00:00+8:00'), 'UTC', null, '+8:00'],
            ['2015-01-01+06:00', new \DateTime('2014-12-30 22:00:00-20:00'), 'UTC', null, '-20:00'],
        ];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDeserializeInvalidDate()
    {
        $element = new \SimpleXMLElement("<Date>2015-01-01T</Date>");
        $this->handler->deserializeDate($this->visitor, $element, [], $this->context);
    }

    /**
     * @requires PHP 7.0
     * @dataProvider getDeserializeDateTimeWithTimezone
     * @param string $date
     * @param \DateTime $expected
     * @param string $defaultTimezone
     * @param string $serializeTimezone
     * @param string $deserializeTimezone
     */
    public function testDeserializeDateTimeWithTimezone(
        $date,
        \DateTime $expected,
        $defaultTimezone = 'UTC',
        $serializeTimezone = null,
        $deserializeTimezone = null
    ) {
        $handler = new XmlSchemaDateHandler($defaultTimezone, $serializeTimezone, $deserializeTimezone);
        $element = new \SimpleXMLElement("<Datetime>$date</Datetime>");
        $deserialized = $handler->deserializeDateTime($this->visitor, $element, [], $this->context);
        $this->assertEquals($expected, $deserialized);
    }

    public function getDeserializeDateTimeWithTimezone()
    {
        $tzPlus6 = new \DateTimeZone('+06:00');
        $tzMinus20 = new \DateTimeZone('-20:00');

        return [
            // timezone is not set (default timezone is used)
            ['2015-01-01T10:01:01', new \DateTime('2015-01-01 10:01:01'), 'UTC'],
            ['2015-01-01T10:01:01', new \DateTime('2015-01-01 10:01:01', $tzPlus6), '+06:00'],
            ['2015-01-01T10:01:01', new \DateTime('2015-01-01 10:01:01', $tzMinus20), '-20:00'],
            // timezone is set (note that default timezone is ignored by php DateTime constructor)
            ['2015-01-01T10:01:01Z', new \DateTime('2015-01-01 10:01:01+0:00'), 'UTC'],
            ['2015-01-01T10:01:01Z', new \DateTime('2015-01-01 10:01:01+0:00'), '+06:00'],
            ['2015-01-01T10:01:01+06:00', new \DateTime('2015-01-01 10:01:01+06:00'), 'UTC'],
            ['2015-01-01T10:01:01+06:00', new \DateTime('2015-01-01 10:01:01+6:00'), '-20:00'],
            // deserialize timezone is set (here we expect timezone shifting to required one)
            ['2015-01-01T10:01:01Z', new \DateTime('2015-01-01 10:01:01+0:00'), 'UTC', null, 'UTC'],
            ['2015-01-01T10:01:01Z', new \DateTime('2015-01-01 16:01:01+6:00'), 'UTC', null, '+06:00'],
            ['2015-01-01T10:01:01Z', new \DateTime('2014-12-31 14:01:01-20:00'), 'UTC', null, '-20:00'],
            ['2015-01-01T10:01:01+06:00', new \DateTime('2015-01-01 04:01:01+0:00'), 'UTC', null, '+0:00'],
            ['2015-01-01T10:01:01+06:00', new \DateTime('2015-01-01 12:01:01+8:00'), 'UTC', null, '+8:00'],
            ['2015-01-01T10:01:01+06:00', new \DateTime('2014-12-31 08:01:01-20:00'), 'UTC', null, '-20:00'],
        ];
    }

    /**
     * @requires PHP 7.0
     * @dataProvider getDeserializeTimeWithTimezone
     * @param string $date
     * @param \DateTime $expected
     * @param string $defaultTimezone
     * @param string $serializeTimezone
     * @param string $deserializeTimezone
     */
    public function testDeserializeTimeWithTimezone(
        $date,
        \DateTime $expected,
        $defaultTimezone = 'UTC',
        $serializeTimezone = null,
        $deserializeTimezone = null
    ) {
        $handler = new XmlSchemaDateHandler($defaultTimezone, $serializeTimezone, $deserializeTimezone);
        $element = new \SimpleXMLElement("<Time>$date</Time>");
        $deserialized = $handler->deserializeTime($this->visitor, $element, [], $this->context);
        $this->assertEquals($expected, $deserialized);
    }

    public function getDeserializeTimeWithTimezone()
    {
        $tzPlus6 = new \DateTimeZone('+06:00');
        $tzMinus12 = new \DateTimeZone('-12:00');

        return [
            // timezone is not set (default timezone is used)
            ['10:01:01', new \DateTime('10:01:01'), 'UTC'],
            ['10:01:01', new \DateTime('10:01:01', $tzPlus6), '+06:00'],
            ['10:01:01', new \DateTime('10:01:01', $tzMinus12), '-12:00'],
            // timezone is set (note that default timezone is ignored by php DateTime constructor)
            ['10:01:01Z', new \DateTime('10:01:01+0:00'), 'UTC'],
            ['10:01:01+06:00', new \DateTime('10:01:01+06:00'), 'UTC'],
            ['10:01:01-12:00', new \DateTime('10:01:01-12:00'), 'UTC'],
            // deserialize timezone is set (here we expect timezone shifting to required one)
            ['10:01:01Z', new \DateTime('10:01:01+00:00'), 'UTC', null, 'UTC'],
            ['10:01:01Z', new \DateTime('16:01:01+06:00'), 'UTC', null, '+06:00'],
            ['10:01:01Z', new \DateTime('02:01:01-08:00'), 'UTC', null, '-8:00'],
            ['10:01:01+06:00', new \DateTime('04:01:01+0:00'), 'UTC', null, '+0:00'],
            ['10:01:01+00:00', new \DateTime('18:01:01+8:00'), 'UTC', null, '+8:00'],
            ['10:01:01+08:00', new \DateTime('00:01:01-2:00'), 'UTC', null, '-2:00'],
        ];
    }
}
