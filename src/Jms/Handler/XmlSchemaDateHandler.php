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
    protected $serializeTimezone;
    protected $deserializeTimezone;

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
        );
    }

    /**
     * @param string $defaultTimezone default timezone if timezone is absent in deserialized string
     * @param null $serializeTimezone timezone of serialized date/datetime/time. Works properly since php 7.0
     * @param null $deserializeTimezone timezone of deserialized date/datetime/time. Works properly since php 7.0
     *                                  note that $defaultTimezone may be applied first
     */
    public function __construct($defaultTimezone = 'UTC', $serializeTimezone = null, $deserializeTimezone = null)
    {
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
        $this->serializeTimezone = $serializeTimezone ? new \DateTimeZone($serializeTimezone) : null;
        $this->deserializeTimezone = $deserializeTimezone ? new \DateTimeZone($deserializeTimezone) : null;
    }

    public function deserializeDateIntervalXml(XmlDeserializationVisitor $visitor, $data, array $type){
        $attributes = $data->attributes('xsi', true);
        if (isset($attributes['nil'][0]) && (string) $attributes['nil'][0] === 'true') {
            return null;
        }
        return new \DateInterval((string)$data);
    }

    public function serializeDate(XmlSerializationVisitor $visitor, \DateTime $date, array $type, Context $context)
    {
        $date = $this->prepareDateTimeBeforeSerialize($date);
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
        $date = $this->prepareDateTimeBeforeSerialize($date);
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
        $date = $this->prepareDateTimeBeforeSerialize($date);
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

        if ($this->deserializeTimezone) {
            return $this->parseDateTime($data, $type);
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

        if ($this->deserializeTimezone) {
            $datetime->setTimezone($this->deserializeTimezone);
        }

        return $datetime;
    }

    private function prepareDateTimeBeforeSerialize(\DateTime $date)
    {
        if ($this->serializeTimezone) {
            $dateCopy = clone $date;
            $dateCopy->setTimezone($this->serializeTimezone);
            return $dateCopy;
        }

        return $date;
    }
}

