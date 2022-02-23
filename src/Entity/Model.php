<?php

namespace App\Entity;

use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ModelRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Model
{
    use Timestamp;

    const CHOICES = ['Space', 'Transport', 'Architecture', 'Nature', 'Food', 'Character', 'Abstract'];
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(min=5, max=50)
     */
    private $name;

    /**
     * @ORM\Column(type="json")
     */
    private $extensions = [];

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="owner_id", referencedColumnName="id")
     * @Assert\NotNull
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=Purchase::class, mappedBy="model")
     */
    private $purchases;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $purchaseCount = 0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
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
     * @ORM\ManyToMany(targetEntity=Tag::class, mappedBy="models")
     */
    private $tags;

    /**
     * @Assert\Choice(Model::CHOICES)
     * @ORM\Column(type="string")
     */
    private $category;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return array_unique($this->extensions);
    }

    /**
     * @param array $extensions
     * @return self
     */
    public function setExtensions(array $extensions): self
    {
        $this->extensions = $extensions;

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
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->addModel($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeModel($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * @param Purchase $purchase
     */
    public function addPurchase(Purchase $purchase): void
    {
        $this->purchases[] = $purchase;
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
     * @return self
     */
    public function setCategory($category): self
    {
        $this->category = $category;

        return $this;
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
        $this->purchaseCount = $this->getPurchaseCount() + 1;
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
     * @param int $rating
     */
    public function setRating(int $rating): void
    {
        $this->rating = $this->rating + $rating;
    }
}
