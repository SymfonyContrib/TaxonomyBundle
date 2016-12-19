<?php
/**
 * Doctrine ORM repository for taxonomy terms.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Vocabulary;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;

class TermRepository extends MaterializedPathRepository
{
    /**
     * Get a query builder for with vocab and term name options.
     *
     * @param null|string $vocabName
     * @param null|string $term
     *
     * @return QueryBuilder
     */
    public function getTermQb($vocabName = null, $term = null)
    {
        $qb = $this->createQueryBuilder('t');

        if ($vocabName) {
            $qb->addSelect('v')
                ->innerJoin('t.vocabulary', 'v')
                ->andWhere('v.name = :vocabName')
                ->setParameter('vocabName', $vocabName);
        }
        if ($term) {
            $qb->andWhere('t.name = :name')
                ->setParameter('name', $term);
        }

        return $qb;
    }

    /**
     * Get a query builder for creating a term tree.
     *
     * @param string $vocabName
     * @param bool   $all
     *
     * @return QueryBuilder
     */
    public function getTermTreeQb($vocabName, $all = false)
    {
        $qb    = $this->getNodesHierarchyQueryBuilder(null, false, [], false);
        $alias = (string)$qb->getDQLPart('select')[0];

        if ($all === false) {
            $qb->andWhere($alias.'.enabled = :enabled')
                ->setParameter('enabled', true);
        }

        $qb->addSelect('v')
            ->innerJoin($alias . '.vocabulary', 'v')
            ->andWhere('v.name = :vocabName')
            ->setParameter(':vocabName', $vocabName)
            ->addSelect('p')
            ->leftJoin($alias . '.parent', 'p');

        return $qb;
    }

    /**
     * Get an ordered nested array of terms.
     *
     * @param null|string|Vocabulary $vocab
     * @param bool $all
     * @param bool $reset
     *
     * @return array
     */
    public function getTree($vocab = null, $all = false, $reset = false)
    {
        static $tree;

        $vocabName = $vocab;
        if ($vocab instanceof Vocabulary) {
            $vocabName = $vocab->getName();
        }

        if ($reset || !empty($tree[$vocabName])) {
            return $tree[$vocabName];
        }

        // Get all terms in this vocabulary.
        $terms = $this->getTermTreeQb($vocabName, $all)->getQuery()->getResult();

        // Create a map of terms and their weight value.
        // Weight is used as the tree array key to allow for easy sorting.
        $map = [];
        foreach ($terms as $term) {
            $map[$term->getName()] = (int)$term->getWeight();
        }

        // Create the multi-dimensional array.
        $vocabTree = [];
        foreach ($terms as $term) {
            // Get the terms parentage tree as an array.
            $pathParts = explode('/', $term->getPath());
            // Remove the term itself.
            $leaf = array_pop($pathParts);
            // The tree is built through referential values.
            $branch = & $vocabTree;
            // Loop through the parentage tree and ensure array levels exist.
            foreach ($pathParts as $part) {
                $branch = & $branch[$map[$part]]['children'];
            }
            if (isset($branch[$map[$leaf]])) {
                $branch[max(array_keys($branch)) + 1]['term'] = $term;
            } else {
                $branch[$map[$leaf]]['term'] = $term;
            }
        }

        // Sort the array.
        $sort = function (&$array) use (&$sort) {
            foreach ($array as &$item) {
                if (!empty($item['children']) && count($item['children']) > 1) {
                    $sort($item['children']);
                }
            }
            if (!empty($array) && count($array) > 1) {
                ksort($array, SORT_NUMERIC);
            }
        };
        $sort($vocabTree);

        $tree[$vocabName] = array_values($vocabTree);

        return $tree[$vocabName];
    }

    /**
     * Get a ordered flat list of terms.
     *
     * @param string|Vocabulary $vocab
     * @param bool              $all
     *
     * @return array
     */
    public function getFlatTree($vocab, $all = false)
    {
        $tree   = $this->getTree($vocab, $all);
        $result = [];
        $result = $this->flattenTree($tree, $result);

        return $result;
    }

    /**
     * Transform a tree into a flat array.
     *
     * @param array $tree
     * @param array $result
     *
     * @return array
     */
    public function flattenTree(array $tree, array &$result)
    {
        foreach ($tree as $branch) {
            $result[$branch['term']->getId()] = $branch['term'];
            if (isset($branch['children'])) {
                $this->flattenTree($branch['children'], $result);
            }
        }

        return $result;
    }

    /**
     * Get a term in a vocabulary or null if not found.
     *
     * @param string $name
     * @param string $vocabName
     *
     * @return null|Term
     */
    public function getTerm($name, $vocabName)
    {
        $qb = $this->getTermQb($vocabName, $name);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get specific terms in a vocabulary.
     *
     * @param array $terms
     * @param string $vocabName
     *
     * @return array
     */
    public function getTerms(array $terms, $vocabName)
    {
        $qb = $this->getTermQb($vocabName);
        $qb->andWhere('t.name IN (:names)')
            ->setParameter('names', $terms);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all terms in a vocabulary.
     *
     * @param string $vocabName
     *
     * @return array
     */
    public function getTermsInVocabulary($vocabName)
    {
        $results = $this->getTermQb($vocabName)->getQuery()->getResult();

        $terms = [];
        foreach ($results as $term) {
            $terms[$term->getId()] = $term;
        }

        return $terms;
    }

    /**
     * Auto-complete search.
     *
     * @param null|string $vocabName
     * @param null|string $term
     *
     * @return array
     */
    public function searchTerms($vocabName = null, $term = null)
    {
        $qb = $this->createQueryBuilder('t');

        if ($vocabName) {
            $qb->innerJoin('t.vocabulary', 'v')
               ->andWhere('v.name = :vocab')
               ->setParameter('vocab', $vocabName);
        }

        if ($term && $vocabName) {
            $qb->andWhere('t.name LIKE :term')
               ->setParameter('term', '%'.$term.'%');
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Create a term entity.
     *
     * @param string            $name
     * @param string|Vocabulary $vocab
     * @param bool              $flush
     *
     * @return Term
     */
    public function createTerm($name, $vocab, $flush = false)
    {
        $em = $this->getEntityManager();

        if (is_string($vocab)) {
            $vocabRepo = $em->getRepository(Vocabulary::class);
            $vocab     = $vocabRepo->findOneBy(['name' => $vocab]);
        }

        $term = new Term();
        $term->setName($name)
             ->setVocabulary($vocab);


        $em->persist($term);
        if ($flush) {
            $em->flush();
        }

        return $term;
    }

    /**
     * Create multiple term entities.
     *
     * @param array $terms
     *
     * @return array
     */
    public function createTerms(array $terms)
    {
        $entities = [];
        foreach ($terms as $data) {
            $entities[] = $this->createTerm($data['name'], $data['vocabulary']);
        }

        return $entities;
    }

    /**
     * Get a term entity or create it, if it does not exist.
     *
     * @param string $name
     * @param string $vocabName
     *
     * @return Term
     */
    public function getOrCreateTerm($name, $vocabName)
    {
        $term = $this->getTerm($name, $vocabName);

        // Check if term exists.
        if (!$term) {
            $term = $this->createTerm($name, $vocabName);
        }

        return $term;
    }
}
