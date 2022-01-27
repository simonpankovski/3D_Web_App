<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TextureDTO
{
    /**
     * @Assert\PositiveOrZero
     */
    private $id;

    private $rating;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(min={5}, max={255})
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Email
     */
    private $ownerEmail;

    /**
     * @Assert\NotNull
     */
    private $purchases;

    /**
     * @Assert\PositiveOrZero
     */
    private $price;

    /**
     * @Assert\Type(type="boolean")
     */
    private $approved;

    /**
     * @Assert\Type(type="array")
     */
    private $thumbnailLinks;

    /**
     * @Assert\NotNull
     * @Assert\DateTime
     */
    private $createdOn;

    /**
     * @Assert\NotNull
     * @Assert\DateTime
     */
    private $updatedOn;

    private $purchaseCount;

    /**
     * @param $id
     * @param $rating
     * @param $name
     * @param $ownerEmail
     * @param $purchases
     * @param $price
     * @param $approved
     * @param $createdOn
     * @param $updatedOn
     * @param $purchaseCount
     * @param $thumbnailLinks
     */
    public function __construct(
        $id,
        $rating,
        $name,
        $ownerEmail,
        $purchases,
        $price,
        $approved,
        $createdOn,
        $updatedOn,
        $purchaseCount,
        $thumbnailLinks
    ) {
        $this->id = $id;
        $this->rating = $rating;
        $this->name = $name;
        $this->ownerEmail = $ownerEmail;
        $this->purchases = $purchases;
        $this->price = $price;
        $this->approved = $approved;
        $this->createdOn = $createdOn;
        $this->updatedOn = $updatedOn;
        $this->purchaseCount = $purchaseCount;
        $this->thumbnailLinks = $thumbnailLinks;
    }

    /**
     * @return mixed
     */
    public function getPurchaseCount()
    {
        return $this->purchaseCount;
    }

    /**
     * @param mixed $purchaseCount
     */
    public function setPurchaseCount($purchaseCount): void
    {
        $this->purchaseCount = $purchaseCount;
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
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating): void
    {
        $this->rating = $rating;
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
    public function getOwnerEmail()
    {
        return $this->ownerEmail;
    }

    /**
     * @param mixed $ownerEmail
     */
    public function setOwnerEmail($ownerEmail): void
    {
        $this->ownerEmail = $ownerEmail;
    }

    /**
     * @return mixed
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * @param mixed $purchases
     */
    public function setPurchases($purchases): void
    {
        $this->purchases = $purchases;
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
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved): void
    {
        $this->approved = $approved;
    }

    /**
     * @return mixed
     */
    public function getThumbnailLinks()
    {
        return $this->thumbnailLinks;
    }

    /**
     * @param mixed $thumbnailLinks
     */
    public function setThumbnailLinks($thumbnailLinks): void
    {
        $this->thumbnailLinks = $thumbnailLinks;
    }

    /**
     * @return mixed
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @param mixed $createdOn
     */
    public function setCreatedOn($createdOn): void
    {
        $this->createdOn = $createdOn;
    }

    /**
     * @return mixed
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * @param mixed $updatedOn
     */
    public function setUpdatedOn($updatedOn): void
    {
        $this->updatedOn = $updatedOn;
    }
}