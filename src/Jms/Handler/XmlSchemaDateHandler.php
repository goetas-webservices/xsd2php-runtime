<?php
namespace GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use RuntimeException;

class XmlSchemaDateHandler implements SubscribingHandlerInterface
{

    protected $defaultTimezone;

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date',
                'method' => 'deserializeDate'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Date',
                'method' => 'serializeDate'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'deserializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\DateTime',
                'method' => 'serializeDateTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'deserializeTime'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\XMLSchema\Time',
                'method' => 'serializeTime'
            ),
            array(
                'type' => 'DateInterval',
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'method' => 'deserializeDateIntervalXml',
            ),
            array(
                'type' => 'DateInterval',
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'method' => 'serializeDateInterval',
            ),
        );
    }

    public function __construct($defaultTimezone = 'UTC')
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);

    }

    public function deserializeDateIntervalXml(XmlDeserializationVisitor $visitor, $data, array $type){
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string) $attributes['nil'][0] === 'true') {
            return null;
        }

        //Accept negative intervals like -PT1M23S.  Safe to assume that "-" doesn't exist elsewhere in a valid interval spec.
        $interval = str_replace('-', '', (string)$data, $count);
        $dateInterval = new \DateInterval($interval);

        //Invert if a negative sign was found
        $dateInterval->invert = !!$count;

        return $dateInterval;
    }

    public function serializeDateInterval(XmlSerializationVisitor $visitor, \DateInterval $interval, array $type, Context $context)
    {
        $date = array_filter(array(
            'Y' => $interval->y,
            'M' => $interval->m,
            'D' => $interval->d
        ));

        // Reading all non-zero time parts.
        $time = array_filter(array(
            'H' => $interval->h,
            'M' => $interval->i,
            'S' => $interval->s
        ));

        $specString = 'P';

        // Adding each part to the spec-string.
        foreach ($date as $key => $value) {
            $specString .= $value . $key;
        }
        if (count($time) > 0) {
            $specString .= 'T';
            foreach ($time as $key => $value) {
                $specString .= $value . $key;
            }
        }

        return $visitor->visitSimpleString($specString, $type, $context);
    }

    public function serializeDate(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        $v = $date->format('Y-m-d');

        return $visitor->visitSimpleString($v, $type, $context);
    }

    public function deserializeDate(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string)$attributes['nil'][0] === 'true') {
            return null;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(Z|([+-]\d{2}:\d{2}))?$/', $data)) {
            throw new RuntimeException(sprintf('Invalid date "%s", expected valid XML Schema date string.', $data));
        }

        return $this->parseDateTime($data, $type);
    }

    public function serializeDateTime(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {

        $v = $date->format(\DateTime::W3C);

        return $visitor->visitSimpleString($v, $type, $context);
    }

    public function deserializeDateTime(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string)$attributes['nil'][0] === 'true') {
            return null;
        }

        return $this->parseDateTime($data, $type);

    }

    public function serializeTime(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        $v = $date->format('H:i:s');
        if ($date->getTimezone()->getOffset($date) !== $this->defaultTimezone->getOffset($date)) {
            $v .= $date->format('P');
        }
        return $visitor->visitSimpleString($v, $type, $context);
    }

    public function deserializeTime(XmlDeserializationVisitor $visitor, $data, array $type)
    {
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string)$attributes['nil'][0] === 'true') {
            return null;
        }

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
}

