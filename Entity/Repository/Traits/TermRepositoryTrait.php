<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository\Traits;

use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Vocabulary;

trait TermRepositoryTrait
{
    public function createTerm($name, $vocab, array $data = [], $flush = true)
    {
        if (is_string($vocab)) {
            $vocab = $this->getVocabulary($vocab);
        }

        $term = new Term();
        $term->setName($name)
             ->setVocabulary($vocab);

        $this->em->persist($term);

        if ($flush) {
            $this->em->flush($term);
        }

        // @todo: Fire event.

        return $term;
    }

    public function createTerms(array $terms)
    {
        $entities = [];
        foreach ($terms as $data) {
            $entities[] = $this->createTerm($data['name'], $data['vocabulary'], $data, false);
        }

        return $entities;
    }

    public function deleteTerm($term)
    {
        if (is_string($term)) {
            $term = $this->getTerm($term);
        }

        $this->em->remove($term);
        $this->em->flush($term);

        // @todo: Fire event.
    }

    public function deleteTerms(array $termNames)
    {
        $dql = "DELETE TaxonomyBundle:Term t
                WHERE t.name IN (:names)";

        $this->em->createQuery($dql)
            ->execute(['names' => $termNames]);

        // @todo: Fire event.
    }

    public function getTermById($termId)
    {
        return $this->em->getRepository('TaxonomyBundle:Term')->find($termId);

        /*$dql = "SELECT t
                FROM TaxonomyBundle:Term t
                WHERE t.id = :id";

        return $this->em->createQuery($dql)
            ->setParameter('id', $termId)
            ->getOneOrNullResult();*/
    }

    public function getTerm($name, $vocab = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('t')
            ->from('TaxonomyBundle:Term', 't')
            ->where('t.name = :name');

        if ($vocab) {
            $qb->innerJoin('t.vocabulary', 'v')
                ->andWhere('v.name = :vocab');
        }

        return $qb->getQuery()
            ->setParameter('name', $name)
            ->setParameter('vocab', $vocab)
            ->getOneOrNullResult();
    }

    public function getTerms(array $terms)
    {
        $dql = "SELECT t
                FROM TaxonomyBundle:Term t
                WHERE t.name IN (:names)";

        return $this->em->createQuery($dql)
            ->setParameter('names', $terms)
            ->getResult();
    }

    public function getTermsInVocabulary($vocab)
    {
        $dql = "SELECT t
                FROM TaxonomyBundle:Term t
                INNER JOIN t.vocabulary v
                WHERE v.name = :vocab";

        $results = $this->em->createQuery($dql)
            ->setParameter('vocab', $vocab)
            ->getResult();

        $qb = $this->termRepo->getNodesHierarchyQueryBuilder();

        $terms = [];
        foreach ($results as $term) {
            $terms[$term->getId()] = $term;
        }

        return $terms;
    }

    public function getOrCreateTerm($name, $vocab)
    {
        // Check if term exists.
        if ($term = $this->getTerm($name, $vocab)) {
            return $term;
        } else {
            return $this->createTerm($name, $vocab);
        }
    }

    public function getTermsQB($vocab)
    {
        $qb = $this->termRepo->getNodesHierarchyQueryBuilder(null, false, [], false);
        $alias = (string)$qb->getDQLPart('select')[0];

        $qb->addSelect('v')
            ->innerJoin($alias . '.vocabulary', 'v')
            ->andWhere('v.name = :vocab')
            ->setParameter(':vocab', $vocab);

        $qb->addSelect('p')
            ->leftJoin($alias . '.parent', 'p');


        return $qb;
    }

    public function getTree($vocab = null, $reset = false)
    {
        static $tree;

        if ($reset || !empty($tree)) {
            return $tree;
        }

        $vocabName = $vocab;
        if ($vocab instanceof Vocabulary) {
            $vocabName = $vocab->getName();
        }

        // Get all terms in this vocabulary.
        $terms = $this->getTermsQB($vocabName)->getQuery()->getResult();

        // Create a map of terms and their weight value.
        // Weight is used as the tree array key to allow for easy sorting.
        $map = [];
        foreach ($terms as $term) {
            $map[$term->getName()] = (int)$term->getWeight();
        }

        // Create the multi-dimensional array.
        $tree = [];
        foreach ($terms as $term) {
            // Get the terms parentage tree as an array.
            $pathParts = explode('/', $term->getPath());
            // Remove the term itself.
            $leaf = array_pop($pathParts);
            // The tree is built through referential values.
            $branch = &$tree;
            // Loop through the parentage tree and ensure array levels exist.
            foreach ($pathParts as $part) {
                $branch = &$branch[$map[$part]]['children'];
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
        $sort($tree);

        $tree = array_values($tree);

        return $tree;
    }

    public function getFlatTree($vocab)
    {
        $tree   = $this->getTree($vocab);
        $result = [];
        $result = $this->flattenTree($tree, $result);

        return $result;
    }

    public function flattenTree($tree, &$result)
    {
        foreach ($tree as $branch) {
            $result[$branch['term']->getId()] = $branch['term'];
            if (isset($branch['children'])) {
                $this->flattenTree($branch['children'], $result);
            }
        }

        return $result;
    }
}
