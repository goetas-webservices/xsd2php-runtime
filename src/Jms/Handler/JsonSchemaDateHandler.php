<?php
namespace GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use RuntimeException;

class JsonSchemaDateHandler implements SubscribingHandlerInterface
{

    protected $defaultTimezone;

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date',
                'method' => 'deserializeDate'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date',
                'method' => 'serializeDate'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'deserializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'serializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'deserializeTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'serializeTime'
            ),
            array(
                'type' => 'DateInterval',
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'method' => 'deserializeDateIntervalXml',
            ),
        );
    }

    public function __construct($defaultTimezone = 'UTC')
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);

    }

    public function deserializeDateIntervalXml(JsonDeserializationVisitor $visitor, $data, array $type){
        return $this->createDateInterval((string)$data);
    }

    public function serializeDate(JsonSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        return $date->format('Y-m-d');
    }

    public function deserializeDate(JsonDeserializationVisitor $visitor, $data, array $type)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(Z|([+-]\d{2}:\d{2}))?$/', $data)) {
            throw new RuntimeException(sprintf('Invalid date "%s", expected valid XML Schema date string.', $data));
        }

        return $this->parseDateTime($data, $type);
    }

    public function serializeDateTime(JsonSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {

        return $date->format(\DateTime::W3C);
    }

    public function deserializeDateTime(JsonDeserializationVisitor $visitor, $data, array $type)
    {
        return $this->parseDateTime($data, $type);

    }

    public function serializeTime(JsonSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        $v = $date->format('H:i:s');
        if ($date->getTimezone()->getOffset($date) !== $this->defaultTimezone->getOffset($date)) {
            $v .= $date->format('P');
        }
        return $v;
    }

    public function deserializeTime(JsonDeserializationVisitor $visitor, $data, array $type)
    {
        $data = (string)$data;

        return new \DateTime($data, $this->defaultTimezone);
    }

    private function parseDateTime($data, array $type)
    {
        $timezone = isset($type['params'][1]) ? new \DateTimeZone($type['params'][1]) : $this->defaultTimezone;
        $datetime = new \DateTime((string)$data, $timezone);
        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected valid XML Schema dateTime string.', $data));
        }

        return $datetime;
    }

    private function createDateInterval($interval){
        $f = 0.0;
        if (preg_match('~\.\d+~',$interval,$match)) {
            $interval = str_replace($match[0], "", $interval);
            $f = (float)$match[0];
        }
        $di = new \DateInterval($interval);
        // milliseconds are only available from >=7.1
        if(isset($di->f)){
            $di->f= $f;
        }

        return $di;
    }
}

