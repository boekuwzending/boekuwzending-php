<?php

namespace Boekuwzending\Serializer;

use Boekuwzending\Resource\Address;
use Boekuwzending\Resource\Contact;
use Boekuwzending\Resource\Order;
use Boekuwzending\Resource\OrderLine;

/**
 * Class OrderSerializer
 */
class OrderSerializer implements SerializerInterface
{
    /**
     * @param Order $data
     * @return array
     */
    public function serialize($data): array
    {
        $serializer = new Serializer();

        $lines = [];
        foreach($data->getOrderLines() as $line) {
            $lines[] = $serializer->serialize($line);
        }

        return [
            'externalId' => $data->getExternalId(),
            'reference' => $data->getReference(),
            'createdAtSource' => $data->getCreatedAtSource(),
            'orderLines' => $lines,
            'shipTo' => [
                'contact' => $serializer->serialize($data->getShipToContact()),
                'address' => $serializer->serialize($data->getShipToAddress()),
            ]
        ];
    }

    /**
     * @param array $data
     * @param string $dataType
     * @return Order
     */
    public function deserialize(array $data, string $dataType): Order
    {
        $serializer = new Serializer();

        $lines = [];
        foreach($data['orderLines'] as $line) {
            $lines[] = $serializer->deserialize($line, OrderLine::class);
        }

        $order = new Order();
        $order->setExternalId($data['externalId']);
        $order->setReference($data['reference']);
        $order->setCreatedAtSource($data['createdAtSource']);
        $order->setOrderLines($lines);
        $order->setShipToContact($serializer->deserialize($data['shipTo']['contact'], Contact::class));
        $order->setShipToAddress($serializer->deserialize($data['shipTo']['address'], Address::class));

        return $order;
    }
}