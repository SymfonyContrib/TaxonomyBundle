<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle;

use Doctrine\Orm\EntityManager;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository\VocabularyRepository;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository\TermRepository;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Vocabulary;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\TermMap;
use SymfonyContrib\Bundle\TaxonomyBundle\Model\TaxonomyOwnerInterface;

/**
 * Taxonomy manager service.
 */
class Taxonomy
{
    /** @var EntityManager */
    protected $em;

    /** @var VocabularyRepository */
    protected $vocabRepo;

    /** @var TermRepository */
    protected $termRepo;

    /**
     * Taxonomy constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get the vocabulary Doctrine repository.
     *
     * @return VocabularyRepository
     */
    public function getVocabRepo()
    {
        return $this->vocabRepo = $this->vocabRepo ?: $this->em->getRepository('TaxonomyBundle:Vocabulary');
    }

    /**
     * Get the term Doctrine repository.
     *
     * @return TermRepository
     */
    public function getTermRepo()
    {
        return $this->termRepo = $this->termRepo ? : $this->em->getRepository('TaxonomyBundle:Term');
    }

    /**
     * Autocomplete search.
     *
     * @param null|string $vocabName
     * @param null|string $term
     * @return array
     */
    public function searchTerms($vocabName = null, $term = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('partial t.{id, name}')
            ->from('TaxonomyBundle:Term', 't');

        if ($vocabName) {
            $qb->innerJoin('t.vocabulary', 'v')
                ->where('v.name = :vocab')
                ->setParameter('vocab', $vocabName);
        }

        if ($term && $vocabName) {
            $qb->andWhere('t.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        } elseif ($term && !$vocabName) {
            $qb->where('t.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Create a term entity.
     *
     * @param string $name
     * @param string|Vocabulary $vocab
     * @param bool $flush
     * @return Term
     */
    public function createTerm($name, $vocab, $flush = true)
    {
        if (is_string($vocab)) {
            $vocab = $this->getVocabRepo()->findOneBy(['name' => $vocab]);
        }

        $term = new Term();
        $term->setName($name)
            ->setVocabulary($vocab);

        $this->em->persist($term);
        if ($flush) {
            $this->em->flush();
        }

        return $term;
    }

    /**
     * Create multiple term entities.
     *
     * @param array $terms
     * @return array
     */
    public function createTerms(array $terms)
    {
        $entities = [];
        foreach ($terms as $data) {
            $entities[] = $this->createTerm($data['name'], $data['vocabulary'], false);
        }

        return $entities;
    }

    /**
     * Get a term entity or create it, if it does not exist.
     *
     * @param string $name
     * @param string $vocabName
     * @return Term
     */
    public function getOrCreateTerm($name, $vocabName)
    {
        // Check if term exists.
        if ($term = $this->getTermRepo()->getTerm($name, $vocabName)) {
            return $term;
        } else {
            return $this->createTerm($name, $vocabName);
        }
    }

    /**
     * Map a term to content.
     *
     * @param Term                   $term
     * @param TaxonomyOwnerInterface $owner
     * @param string                 $field
     *
     * @return TermMap
     */
    public function mapTerm(Term $term, TaxonomyOwnerInterface $owner, $field)
    {
        $termMap = new TermMap();
        $termMap->setTerm($term)
            ->setOwner($owner->getTaxonomyOwner())
            ->setOwnerId($owner->getTaxonomyOwnerId())
            ->setField($field);
        $this->em->persist($termMap);
        $this->em->flush();

        return $termMap;
    }

    /**
     * Remove a map between term and content.
     *
     * @param Term $term
     * @param TaxonomyOwnerInterface $owner
     */
    public function unmapTerm(Term $term, TaxonomyOwnerInterface $owner)
    {
        $dql = "DELETE TaxonomyBundle:TermMap tm
                WHERE tm.termId = :termId
                    AND tm.owner = :owner
                    AND tm.ownerId = :ownerId";

        $this->em->createQuery($dql)->execute([
            'termId'  => $term->getId(),
            'owner'   => $owner->getTaxonomyOwner(),
            'ownerId' => $owner->getTaxonomyOwnerId(),
        ]);
    }

    /**
     * Remove all maps to an ownerId, an owner, or everything.
     *
     * @param TaxonomyOwnerInterface $owner
     * @param string $for [ownerId, owner, truncate]
     * @param null|string $vocabName
     */
    public function unmapAllTerms(TaxonomyOwnerInterface $owner, $for = 'ownerId', $vocabName = null)
    {
        // Do not unmap terms for new entities.
        if ($for === 'ownerId' && $owner->getTaxonomyOwnerId() === null) {
            return;
        }

        // Doctrine delete DQL does not support join statements.
        $sql = "DELETE tm.*
                FROM taxonomy_term_map tm";

        if ($for !== 'truncate') {
            $sql .= " INNER JOIN taxonomy_term t
                        ON tm.tid = t.id
                    INNER JOIN taxonomy_vocabulary v
                        ON t.vid = v.id";
        }

        $params = [];
        switch ($for) {
            case 'ownerId':
                $sql .= " WHERE tm.owner = :owner
                            AND tm.oid = :ownerId";
                $params = [
                    'owner' => $owner->getTaxonomyOwner(),
                    'ownerId' => $owner->getTaxonomyOwnerId(),
                ];
                if ($vocabName) {
                    $sql .= " AND v.name = :vocabName";
                    $params['vocabName'] = $vocabName;
                }
                break;

            case 'owner':
                $sql .= " WHERE tm.owner = :owner";
                $params = [
                    'owner' => $owner->getTaxonomyOwner(),
                ];
                break;

            case 'field':
                $sql .= " WHERE tm.owner = :owner
                            AND tm.oid = :ownerId
                            AND tm.field = :field";
                $params = [
                    'owner'   => $owner->getTaxonomyOwner(),
                    'ownerId' => $owner->getTaxonomyOwnerId(),
                    'field'   => $field,
                ];
                if ($vocabName) {
                    $sql .= " AND v.name = :vocabName";
                    $params['vocabName'] = $vocabName;
                }
                break;

            case 'truncate':
                // USE WITH EXTREME CAUTION! DELETES ALL TERMMAP TABLE ROWS.
                break;
        }

        $this->em->getConnection()->executeUpdate($sql, $params);

        // @todo: Fire event.
    }

    /**
     * Get all terms mapped to this owner or owner ID.
     *
     * @param string $owner
     * @param mixed $ownerId
     * @return array
     */
    public function getMappedTerms($owner, $ownerId = false)
    {
        // Return none for new entities.
        if (is_null($ownerId)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('t, v', 'm')
            ->from('TaxonomyBundle:Term', 't')
            ->innerJoin('t.map', 'm')
            ->innerJoin('t.vocabulary', 'v')
            ->where('m.owner = :owner')
            ->setParameter('owner', $owner);

        if ($ownerId) {
            $qb->andWhere('m.ownerId = :ownerId')
                ->setParameter('ownerId', $ownerId);
        }

        $results = $qb->getQuery()->getResult();

        $terms = [];
        /** @var Term $term */
        foreach ($results as $term) {
            $terms[$term->getMap()->getField()][$term->getId()] = $term;
        }

        return $terms;
    }

    /**
     * Get all content that is mapped to a term.
     *
     * @param string $owner
     * @param string $vocabName
     * @param string $term
     * @return array
     */
    public function getMapsByTerm($owner, $vocabName, &$term)
    {
        // Get term.
        $term = $this->getTermRepo()->getTerm($term, $vocabName);

        // Get owner IDs mapped to term.
        $dql = "SELECT tm.ownerId
                FROM TaxonomyBundle:TermMap tm
                WHERE tm.owner = :owner
                    AND tm.termId = :termId";
        $results = $this->em->createQuery($dql)
            ->setParameter('owner', $owner)
            ->setParameter('termId', $term->getId())
            ->getScalarResult();

        // Prepare owner IDs.
        $ids = [];
        foreach ($results as $row) {
            $ids[] = $row['ownerId'];
        }

        // Get entity identifier field.
        $id = $this->em->getClassMetadata($owner)->getIdentifier()[0];

        // Get entities that match owner IDs.
        return $this->em->getRepository($owner)->findBy([$id => $ids]);
    }
}
