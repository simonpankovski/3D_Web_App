<?php

namespace App\Entity;

use App\Repository\TextureRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TextureRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Texture
{
    use Timestamp;

    const CHOICES = ['Free', 'Dirt', 'Metal', 'Wood', 'Concrete', 'Marble'];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(min=4, max=255)
     */
    private $name;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="owner_id", referencedColumnName="id")
     * @Assert\NotNull
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=TexturePurchase::class, mappedBy="texture")
     */
    private $purchases;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $purchaseCount = 0;

    /**
     * @ORM\Column(type="float", options={"default": 0})
     */
    private $rating = 0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     * @Assert\Type(type="integer")
     * @Assert\PositiveOrZero()
     */
    private $price = 0;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\Type(type="boolean")
     */
    private $approved = false;

    /**
     * @Assert\Choice(Texture::CHOICES)
     * @ORM\Column(type="string")
     */
    private $category;

    public function getId(): ?int
    {
        return $this->id;
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
    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner): void
    {
        $this->owner = $owner;
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
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     * @return self
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * @param bool $approved
     */
    public function setApproved(bool $approved): void
    {
        $this->approved = $approved;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function getPurchaseCount(): int
    {
        return $this->purchaseCount;
    }

    public function setPurchaseCount(): void
    {
        $this->purchaseCount = $this->purchaseCount + 1;
    }

    /**
     * @return float
     */
    public function getRating(): float
    {
        if (!($this->purchaseCount == 0)) return $this->rating / $this->purchaseCount;
        else return 0;
    }

    /**
     * @param float $rating
     */
    public function setRating(float $rating): void
    {
        $this->rating = $rating;
    }
}
