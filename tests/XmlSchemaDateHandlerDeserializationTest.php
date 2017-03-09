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
use JMS\Serializer\XmlDeserializationVisitor;
use Metadata\MetadataFactory;

class XmlSchemaDateHandlerDeserializationTest extends \PHPUnit_Framework_TestCase
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
     * @var XmlDeserializationVisitor
     */
    protected $visitor;

    public function setUp()
    {
        $this->handler = new XmlSchemaDateHandler();
        $this->visitor = new XmlDeserializationVisitor(new IdenticalPropertyNamingStrategy());

        $dispatcher = new EventDispatcher();
        $handlerRegistry= new HandlerRegistry();
        $objectConstructor = new UnserializeObjectConstructor();
        $metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));

        $navigator = new GraphNavigator($metadataFactory, $handlerRegistry, $objectConstructor, $dispatcher);

        $this->visitor->setNavigator($navigator);
    }

    /**
     * @dataProvider getDeserializeDate
     * @param string    $date
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
     * @expectedException \RuntimeException
     */
    public function testDeserializeInvalidDate()
    {
        $element = new \SimpleXMLElement("<Date>2015-01-01T</Date>");
        $this->handler->deserializeDate($this->visitor, $element, [], $this->context);
    }
}
