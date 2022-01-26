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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param array $extensions
     */
    public function setExtensions(array $extensions): void
    {
        $this->extensions = $extensions;
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
     */
    public function setPrice(int $price): void
    {
        $this->price = $price;
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
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags): void
    {
        $this->tags = $tags;
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
}
