<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ModelDTO
{
    /**
     * @Assert\PositiveOrZero
     */
    private $id;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(min={1}, max={500})
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Type(type="array")
     */
    private $extensions;

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
    private $tags;

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

    /**
     * @param $id
     * @param $name
     * @param $extensions
     * @param $ownerEmail
     * @param $purchases
     * @param $price
     * @param $approved
     * @param $createdOn
     * @param $updatedOn
     * @param $thumbnailLinks
     */
    public function __construct(
        $id,
        $name,
        $extensions,
        $ownerEmail,
        $purchases,
        $price,
        $approved,
        $createdOn,
        $updatedOn,
        $thumbnailLinks
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->extensions = $extensions;
        $this->ownerEmail = $ownerEmail;
        $this->purchases = $purchases;
        $this->price = $price;
        $this->approved = $approved;
        $this->createdOn = $createdOn;
        $this->updatedOn = $updatedOn;
        $this->thumbnailLinks = $thumbnailLinks;
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
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param mixed $extensions
     */
    public function setExtensions($extensions): void
    {
        $this->extensions = $extensions;
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
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
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

    /**
     * @return array
     */
    public function getThumbnailLinks(): array
    {
        return $this->thumbnailLinks;
    }

    /**
     * @param array $thumbnailLinks
     */
    public function setThumbnailLinks(array $thumbnailLinks): void
    {
        $this->thumbnailLinks = $thumbnailLinks;
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
}
