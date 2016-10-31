<?php
/**
 * Provides pages for administration of taxonomy terms.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\TermForm;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\TermsSortForm;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\Type\TermSortType;

class AdminTermController extends Controller
{
    /**
     * List of the terms in a vocabulary.
     *
     * @param Request $request
     * @param string $vocabName Machine name of vocabulary.
     * @return Response
     */
    public function listAction(Request $request, $vocabName)
    {
        $em         = $this->getDoctrine()->getManager();
        $taxonomy   = $this->get('taxonomy');
        $vocabulary = $taxonomy->getVocabRepo()->findOneBy(['name' => $vocabName]);
        $terms      = $taxonomy->getTermRepo()->getFlatTree($vocabulary);

        $uri = $request->getRequestUri();

        $form = false;
        if ($vocabulary->isOrderable()) {
            $options = [
                'vocabulary' => $vocabulary,
            ];
            $form = $this->createForm(TermsSortForm::class, ['terms' => $terms], $options);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->flush();

                $msg = 'Saved terms order.';
                $this->get('session')->getFlashBag()->add('success', $msg);

                return $this->redirect($uri);
            }
        }

        return $this->render('TaxonomyBundle:Admin/Term:list.html.twig', [
            'terms'      => $terms,
            'vocabulary' => $vocabulary,
            'form'       => $form ? $form->createView() : false,
            'cancel_url' => $uri,
        ]);
    }

    /**
     * Term add/edit page.
     *
     * @param Request $request
     * @param string $vocabName
     * @param null|string $termId
     * @return RedirectResponse|Response
     */
    public function formAction(Request $request, $vocabName, $termId = null)
    {
        $taxonomy   = $this->get('taxonomy');
        $vocabulary = $taxonomy->getVocabRepo()->findOneBy(['name' => $vocabName]);
        $em         = $this->getDoctrine()->getManager();
        $listUri    = $this->generateUrl('taxonomy_admin_term_list', ['vocabName' => $vocabName]);

        if ($termId) {
            $term = $taxonomy->getTermRepo()->find($termId);
        } else {
            $term = new Term();
            $term->setVocabulary($vocabulary);
        }

        $form = $this->createForm(TermForm::class, $term, [
            'vocabulary' => $vocabulary,
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($term);
            $em->flush();

            $msg = ($termId ? 'Updated ' : 'Added ') . $term->getName();
            $this->get('session')->getFlashBag()->add('success', $msg);

            return $this->redirect($listUri);
        }

        return $this->render('TaxonomyBundle:Admin/Term:form.html.twig', [
            'term'       => $term,
            'form'       => $form->createView(),
            'cancel_url' => $listUri,
        ]);
    }

    /**
     * Delete a term with confirmation.
     *
     * @param string $termId ID of term.
     * @return Response
     */
    public function deleteAction($termId)
    {
        $taxonomy  = $this->get('taxonomy');
        $term      = $taxonomy->getTermRepo()->find($termId);
        $vocabName = $term->getVocabulary()->getName();

        $options = [
            'message' => 'Are you sure you want to <strong>DELETE "' . $term->getName() . '"</strong> in the "' . $vocabName . '" vocabulary?',
            'warning' => 'This can not be undone!',
            'confirm_button_text' => 'Delete',
            'cancel_link_text' => 'Cancel',
            'confirm_action' => [$this, 'termDelete'],
            'confirm_action_args' => [
                'term' => $term,
            ],
            'cancel_url' => $this->generateUrl('taxonomy_admin_term_list', ['vocabName' => $vocabName]),
        ];

        return $this->forward('ConfirmBundle:Confirm:confirm', ['options' => $options]);
    }

    /**
     * Delete confirmation callback.
     *
     * @param array $args
     * @return RedirectResponse
     */
    public function termDelete(array $args)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($args['term']);
        $em->flush();

        $msg = 'Deleted';
        $this->get('session')->getFlashBag()->add('success', $msg);

        return $this->redirect($this->generateUrl('taxonomy_admin_term_list', ['vocabName' => $args['term']->getVocabulary()->getName()]));
    }
}
