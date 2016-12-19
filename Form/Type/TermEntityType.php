<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository\TermRepository;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;

/**
 * Taxonomy entity type form field.
 */
class TermEntityType extends AbstractType
{
    /**
     * @var TermRepository
     */
    public $termRepo;

    /**
     * @param TermRepository $termRepo
     */
    public function __construct(TermRepository $termRepo)
    {
        $this->termRepo = $termRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Use a callable to get vocabulary argument through late processing.
        $choiceList = function (Options $options) {
            return $this->termRepo->getFlatTree($options['vocabulary']);
        };

        $choiceLabel = function (Options $options) {
            return function (Term $term) use ($options) {
                return $term->getHierarchyLabel('--', $options['term_label']);
            };
        };

        $resolver->setDefaults([
            'class'        => 'TaxonomyBundle:Term',
            'vocabulary'   => null,
            'choices'      => $choiceList,
            'choice_label' => $choiceLabel,
            'required'     => false,
            'attr'         => [
                'class' => 'term-parent-id'
            ],
            'term_label'   => 'name',
        ]);

        $resolver->setRequired('vocabulary');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
