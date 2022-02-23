<?php

namespace App\DTO;

class PurchaseDTO
{
    private $name;
    private $type;
    private $price;
    private $objectId;

    /**
     * @param $name
     * @param $type
     * @param $price
     * @param $objectId
     */
    public function __construct($name, $type, $price, $objectId)
    {
        $this->name = $name;
        $this->type = $type;
        $this->price = $price;
        $this->objectId = $objectId;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param mixed $objectId
     */
    public function setObjectId($objectId): void
    {
        $this->objectId = $objectId;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
    }
}