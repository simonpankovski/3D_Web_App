<?php

namespace App\Entity;

use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\Mapping as ORM;

trait Timestamp
{
    /**
     * @ORM\Column(type="datetime")
     */
    private $createdOn;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedOn;

    /**
     * @ORM\PrePersist()
     */
    public function onPrePersist()
    {
        $time = new \DateTime("now");
        $this->createdOn = $time;
        $this->updatedOn = $time;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate()
    {
        $time = new \DateTime("now");
        $this->updatedOn = $time;
    }
}
