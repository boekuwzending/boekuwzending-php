<?php

declare(strict_types=1);

namespace Boekuwzending\Serializer;

use Boekuwzending\Exception\SerializerNotFoundException;
use Boekuwzending\Resource\Address;
use Boekuwzending\Resource\Contact;
use Boekuwzending\Resource\DeliveryInstruction;
use Boekuwzending\Resource\DispatchInstruction;
use Boekuwzending\Resource\Item;
use Boekuwzending\Resource\Label;
use Boekuwzending\Resource\Shipment;
use Boekuwzending\Resource\Tracking;
use Boekuwzending\Resource\TrackingLine;

/**
 * Class Serializer.
 */
class Serializer implements SerializerInterface
{
    /**
     * @var SerializerInterface[]
     */
    private $serializers;

    /**
     * Serializer constructor.
     */
    public function __construct()
    {
        $this->serializers = [
            Shipment::class => new ShipmentSerializer(),
            Contact::class => new ContactSerializer(),
            Address::class => new AddressSerializer(),
            DispatchInstruction::class => new InstructionSerializer(),
            DeliveryInstruction::class => new InstructionSerializer(),
            Item::class => new ItemSerializer(),
            Tracking::class => new TrackingSerializer(),
            TrackingLine::class => new TrackingLineSerializer(),
            Label::class => new LabelSerializer(),
        ];
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function serialize($data): array
    {
        $serializer = $this->getSerializer(get_class($data));

        return $serializer->serialize($data);
    }

    /**
     * @param array  $data
     * @param string $dataType
     *
     * @return mixed
     */
    public function deserialize(array $data, string $dataType)
    {
        $serializer = $this->getSerializer($dataType);

        return $serializer->deserialize($data, $dataType);
    }

    /**
     * @param string $dataType
     *
     * @return SerializerInterface
     */
    private function getSerializer(string $dataType): SerializerInterface
    {
        foreach ($this->serializers as $type => $serializer) {
            if ($type === $dataType) {
                return $serializer;
            }
        }

        throw new SerializerNotFoundException(
            sprintf('No serializer available for type %s', $dataType)
        );
    }
}