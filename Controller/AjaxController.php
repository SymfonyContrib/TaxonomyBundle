<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides pages for administration of the taxonomy system.
 */
class AjaxController extends Controller
{
    /**
     * List of the content associated with a term.
     *
     * @param string $vocabName Machine name of vocabulary.
     * @param string $term Name of term.
     *
     * @return JsonResponse
     */
    public function termSearchAction($vocabName = null, $term = null)
    {
        $taxonomy = $this->get('taxonomy');
        $terms    = $taxonomy->searchTerms($vocabName, $term);

        return $this->json(['terms' => $terms]);
    }
}
