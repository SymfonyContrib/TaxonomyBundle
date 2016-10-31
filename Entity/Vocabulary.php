<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Taxonomy vocabulary.
 */
class Vocabulary
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $desc;

    /**
     * @var string
     */
    protected $orderable;

    /**
     * @var int
     */
    protected $weight;

    /**
     * @var ArrayCollection
     */
    protected $terms;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;


    /**
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        $this->desc      = '';
        $this->weight    = 0;
        $this->orderable = true;
        $this->createdAt = new \DateTime();
    }

    /**
     * Convert object to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Doctrine lifecycle callback.
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (!$args->hasChangedField('updatedAt')) {
            $this->updatedAt = new \DateTime();
        }
    }

    /**
     * @param \DateTime $createdAt
     *
*@return Vocabulary
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * @param string $desc
     * @return Vocabulary
     */
    public function setDesc($desc)
    {
        $this->desc = $desc ?: '';

        return $this;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $id
     * @return Vocabulary
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
     * @param string $name
     * @return Vocabulary
     */
    public function setName($name = null)
    {
        $name = $name ?: $this->label;

        if (preg_match('@[^a-z0-9_]+@', $name)) {
            $name = preg_replace('@[^a-z0-9_]+@', '_', strtolower($name));
        }

        $this->name = $name;

        if (empty($this->label)) {
            $this->label = $name;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $label
     * @return Vocabulary
     */
    public function setLabel($label)
    {
        $this->label = $label;

        if (empty($this->name)) {
            $this->setName();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param ArrayCollection $terms
     * @return Vocabulary
     */
    public function setTerms(ArrayCollection $terms = null)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * @param \DateTime $updatedAt
     *
*@return Vocabulary
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param int $weight
     * @return Vocabulary
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return string
     */
    public function isOrderable()
    {
        return $this->orderable;
    }

    /**
     * @param string $orderable
     *
     * @return Vocabulary
     */
    public function setOrderable($orderable)
    {
        $this->orderable = (bool)$orderable;

        return $this;
    }
}
