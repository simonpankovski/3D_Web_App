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
     * @ORM\Column(type="float")
     * @Assert\Range(min="1", max="5")
     */
    protected $rating;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User", inversedBy="purchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Model", inversedBy="purchases")
     * @ORM\JoinColumn(name="model_id", referencedColumnName="id", nullable=false)
     */
    protected $model;
}