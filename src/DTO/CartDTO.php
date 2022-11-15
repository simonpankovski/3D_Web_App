<?php

namespace App\DTO;

class CartDTO
{
    protected $id;

    protected $username;

    protected $name;

    protected $type;

    protected $price;

    protected $objectId;

    /**
     * @param $username
     * @param $name
     * @param $type
     * @param $price
     * @param $objectId
     * @param $id
     */
    public function __construct($username, $name, $type, $price, $objectId, $id)
    {
        $this->username = $username;
        $this->name = $name;
        $this->type = $type;
        $this->price = $price;
        $this->objectId = $objectId;
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
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
}