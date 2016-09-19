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
     * @return QueryBuilder
     */
    public function getTermTreeQb($vocabName)
    {
        $qb    = $this->getNodesHierarchyQueryBuilder(null, false, [], false);
        $alias = (string)$qb->getDQLPart('select')[0];

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
     * @param bool $reset
     * @return array
     */
    public function getTree($vocab = null, $reset = false)
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
        $terms = $this->getTermTreeQb($vocabName)->getQuery()->getResult();

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
     * @return array
     */
    public function getFlatTree($vocab)
    {
        $tree   = $this->getTree($vocab);
        $result = [];
        $result = $this->flattenTree($tree, $result);

        return $result;
    }

    /**
     * Transform a tree into a flat array.
     *
     * @param array $tree
     * @param array $result
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
}
