<?php

namespace Workspace\Entity;

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

    private $approve;

    public function getApprove()
    {
        return $this->approve;
    }

    public function setApprove($value)
    {
        switch ($value) {
            case 'approved':
            case 'submitted':
            case 'rejected':
            case 'not submitted':
                break;
            default:
                throw new \Exception('Invalid approval string: ' . $value);
        }

        $this->approve = $value;
        return $this;
    }

    private $approveMessage;

    public function getApproveMessage()
    {
        return $this->approveMessage;
    }

    public function setApproveMessage($value)
    {
        $this->approveMessage = $value;
        return $this;
    }

    protected $approveTimestamp;

    public function getApproveTimestamp()
    {
        return $this->approveTimestamp;
    }

    public function setApproveTimestamp(\DateTime $value)
    {
        $this->approveTimestamp = $value;
        return $this;
    }

    protected $approveUser;

    public function getApproveUser()
    {
        return $this->approveUser;
    }

    public function setApproveUser($value)
    {
        $this->approveUser = $value;
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
        $this->setApprove('not submitted');
    }
}
