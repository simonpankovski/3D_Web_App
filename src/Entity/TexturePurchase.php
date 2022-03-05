<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TexturePurchase
{
    use Timestamp;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User", inversedBy="texturePurchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Texture", inversedBy="texturePurchases")
     * @ORM\JoinColumn(name="texture_id", referencedColumnName="id", nullable=false)
     */
    protected $texture;

    /**
     * @ORM\Column(type="float", nullable="true")
     */
    protected $rating = 0;
    /**
     * @param $user
     * @param $texture
     */
    public function __construct($user, $texture)
    {
        $this->user = $user;
        $this->texture = $texture;
        $this->rating = 0;
    }

    /**
     * @return float
     */
    public function getRating(): float
    {
        return $this->rating;
    }

    /**
     * @param float $rating
     */
    public function setRating(float $rating): void
    {
        $this->rating = $rating;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getTexture()
    {
        return $this->texture;
    }

    /**
     * @param mixed $texture
     */
    public function setTexture($texture): void
    {
        $this->texture = $texture;
    }
}