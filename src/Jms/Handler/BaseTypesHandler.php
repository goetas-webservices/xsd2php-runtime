<?php
namespace GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;

class BaseTypesHandler implements SubscribingHandlerInterface
{

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf',
                'method' => 'simpleListOfToXml'
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'xml',
                'type' => 'GoetasWebservices\Xsd\XsdToPhp\Jms\SimpleListOf',
                'method' => 'simpleListOfFromXML'
            )
        );
    }

    public function simpleListOfToXml(XmlSerializationVisitor $visitor, $object, array $type, Context $context)
    {

        $newType = array(
            'name' => $type["params"][0]["name"],
            'params' => array()
        );

        $navigator = $context->getNavigator();
        $ret = array();
        foreach ($object as $v) {
            $ret[] = $navigator->accept($v, $newType, $context)->data;
        }

        return $visitor->getDocument()->createTextNode(implode(" ", $ret));
    }

    public function simpleListOfFromXml(XmlDeserializationVisitor $visitor, $node, array $type, Context $context)
    {
        $newType = array(
            'name' => $type["params"][0]["name"],
            'params' => array()
        );
        $ret = array();
        $navigator = $context->getNavigator();
        foreach (explode(" ", (string)$node) as $v) {
            $ret[] = $navigator->accept($v, $newType, $context);
        }
        return $ret;
    }
}

