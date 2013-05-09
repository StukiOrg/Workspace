<?php

namespace SoliantEntityAudit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Revision
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected $comment;

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($value)
    {
        $this->comment = $value;
        return $this;
    }

    protected $timestamp;

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $value)
    {
        $this->timestamp = $value;
        return $this;
    }

    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($value)
    {
        $this->user = $value;
        return $this;
    }

    private $approved;

    public function isApproved()
    {
        return $this->approved;
    }

    public function setApproved($value)
    {
        $this->approved = $value;
        return $this;
    }

    private $revisionEntities;

    public function getRevisionEntities() {
        if (!$this->revisionEntities)
            $this->revisionEntities = new ArrayCollection();

        return $this->revisionEntities;
    }

    public function __construct()
    {
        $this->setTimestamp(new \DateTime());
        $this->setApproved(false);
    }
}
