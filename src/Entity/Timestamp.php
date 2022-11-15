<?php

namespace App\Entity;

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
