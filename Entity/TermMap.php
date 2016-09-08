<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Entity;

/**
 * Taxonomy term map.
 */
class TermMap
{
    /** @var  string */
    protected $id;

    /** @var  string */
    protected $termId;

    /** @var  string */
    protected $owner;

    /** @var  string */
    protected $ownerId;

    /** @var  string */
    protected $field;

    /** @var  \DateTime */
    protected $createdAt;

    /** @var  Term */
    protected $term;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return TermMap
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $id
     *
     * @return TermMap
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $owner
     *
     * @return TermMap
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param string $ownerId
     *
     * @return TermMap
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param string $termId
     *
     * @return TermMap
     */
    public function setTermId($termId)
    {
        $this->termId = $termId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTermId()
    {
        return $this->termId;
    }

    /**
     * @param Term $term
     *
     * @return TermMap
     */
    public function setTerm(Term $term)
    {
        $this->term   = $term;
        $this->termId = $term->getId();

        return $this;
    }

    /**
     * @return Term
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     *
     * @return TermMap
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }
}
