<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Vocabulary;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\VocabularyForm;

/**
 * Provides pages for administration of taxonomy vocabularies.
 */
class AdminVocabController extends Controller
{
    /**
     * List of vocabularies on the site.
     *
     * @return Response
     */
    public function listAction()
    {
        $vocabs = $this->getDoctrine()->getRepository(Vocabulary::class)->findAll();

        return $this->render('TaxonomyBundle:Admin/Vocab:list.html.twig', [
            'vocabs' => $vocabs,
        ]);
    }

    /**
     * Vocabulary add/edit page.
     *
     * @param Request $request
     * @param null|string $vocabName
     *
     * @return RedirectResponse|Response
     */
    public function formAction(Request $request, $vocabName = null)
    {
        $em       = $this->getDoctrine()->getManager();
        $listUri  = $this->generateUrl('taxonomy_admin_vocab_list');

        if ($vocabName) {
            $vocabulary = $em->getRepository(Vocabulary::class)
                ->findOneBy(['name' => $vocabName]);
        } else {
            $vocabulary = new Vocabulary();
        }

        $form = $this->createForm(VocabularyForm::class, $vocabulary);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($vocabulary);
            $em->flush();

            $msg = ($vocabName ? 'Updated ' : 'Added ') . $vocabulary->getLabel();
            $this->get('session')->getFlashBag()->add('success', $msg);

            return $this->redirect($this->generateUrl('taxonomy_admin_vocab_list'));
        }

        return $this->render('TaxonomyBundle:Admin/Vocab:form.html.twig', [
            'vocabulary' => $vocabulary,
            'form'       => $form->createView(),
            'cancel_url' => $listUri,
        ]);
    }

    /**
     * Delete a vocabulary with confirmation.
     *
     * @param string $vocabName Machine name of vocabulary.
     *
     * @return Response
     */
    public function deleteAction($vocabName)
    {
        $options = [
            'message' => 'Are you sure you want to <strong>DELETE</strong> the <strong>"' . $vocabName . '"</strong> vocabulary?',
            'warning' => 'This can not be undone!',
            'confirm_button_text' => 'Delete',
            'cancel_link_text' => 'Cancel',
            'confirm_action' => [$this, 'vocabDelete'],
            'confirm_action_args' => [
                'vocabName' => $vocabName,
            ],
            'cancel_url' => $this->generateUrl('taxonomy_admin_vocab_list'),
        ];

        return $this->forward('ConfirmBundle:Confirm:confirm', ['options' => $options]);
    }

    /**
     * Delete confirmation callback.
     *
     * @param array $args
     *
     * @return RedirectResponse
     */
    public function vocabDelete(array $args)
    {
        $em = $this->getDoctrine()->getManager();
        $vocabulary = $em->getRepository(Vocabulary::class)->findOneBy(['name' => $args['vocabName']]);

        $em->remove($vocabulary);
        $em->flush();

        $msg = 'Deleted';
        $this->get('session')->getFlashBag()->add('success', $msg);

        return $this->redirect($this->generateUrl('taxonomy_admin_vocab_list'));
    }

}
