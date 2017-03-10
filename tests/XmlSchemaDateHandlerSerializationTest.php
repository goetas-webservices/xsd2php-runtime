<?php
namespace GoetasWebservices\Xsd\XsdToPhpRuntime\Tests\Jms\Handler;

use Doctrine\Common\Annotations\AnnotationReader;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\XmlSerializationVisitor;
use Metadata\MetadataFactory;

class XmlSchemaDateHandlerSerializationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlSchemaDateHandler
     */
    protected $handler;
    /**
     * @var SerializationContext
     */
    protected $context;

    /**
     * @var XmlSerializationVisitor
     */
    protected $visitor;

    public function setUp()
    {
        $this->handler = new XmlSchemaDateHandler();
        $this->context = SerializationContext::create();
        $this->visitor = new XmlSerializationVisitor(new IdenticalPropertyNamingStrategy());

        $dispatcher = new EventDispatcher();
        $handlerRegistry= new HandlerRegistry();
        $objectConstructor = new UnserializeObjectConstructor();
        $metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));

        $navigator = new GraphNavigator($metadataFactory, $handlerRegistry, $objectConstructor, $dispatcher);

        $this->visitor->setNavigator($navigator);
    }

    /**
     * @dataProvider getSerializeDateTime
     * @param \DateTime $date
     */
    public function testSerializeDateTime(\DateTime $date, $expected)
    {
        $this->handler->serializeDateTime($this->visitor, $date, [], $this->context);
        $this->assertEquals($expected, $this->visitor->getCurrentNode()->nodeValue);
    }

    public function getSerializeDateTime()
    {
        return [
            [new \DateTime('2015-01-01 12:00+00:00'), '2015-01-01T12:00:00+00:00'],
            [new \DateTime('2015-01-01 12:00:56+00:00'), '2015-01-01T12:00:56+00:00'],
            [new \DateTime('2015-01-01 12:00:56+00:00'), '2015-01-01T12:00:56+00:00'],
            [new \DateTime('2015-01-01 12:00:56+20:00'), '2015-01-01T12:00:56+20:00'],
            [new \DateTime('2015-01-01 12:00:56', new \DateTimeZone("Europe/London")), '2015-01-01T12:00:56+00:00'],
            [new \DateTime('2015-01-01 12:00:56+00:00', new \DateTimeZone("Europe/London")), '2015-01-01T12:00:56+00:00'],
            [new \DateTime('2015-01-01 12:00:56', new \DateTimeZone("Europe/Rome")), '2015-01-01T12:00:56+01:00'],
        ];
    }

    /**
     * @dataProvider getSerializeDate
     * @param \DateTime $date
     * @param string    $expected
     */
    public function testSerializeDate(\DateTime $date, $expected)
    {
        $this->handler->serializeDate($this->visitor, $date, [], $this->context);
        $this->assertEquals($expected, $this->visitor->getCurrentNode()->nodeValue);
    }

    public function getSerializeDate()
    {
        return [
            [new \DateTime('2015-01-01 12:00'), '2015-01-01'],
            [new \DateTime('2015-01-01 12:00:56'), '2015-01-01'],
            [new \DateTime('2015-01-01 12:00:56+00:00'), '2015-01-01'],
            [new \DateTime('2015-01-01 12:00:56+20:00'), '2015-01-01'],
            [new \DateTime('2015-01-01 12:00:56', new \DateTimeZone("Europe/London")), '2015-01-01'],
        ];
    }
}
