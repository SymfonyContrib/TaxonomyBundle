<?php
/**
 * Taxonomy entity type form field.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Term;
use SymfonyContrib\Bundle\TaxonomyBundle\Taxonomy;

class TermEntityType extends AbstractType
{
    /**
     * @var Taxonomy
     */
    public $taxonomy;

    public function __construct(Taxonomy $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Use a callable to get vocabulary argument through late processing.
        $choiceList = function (Options $options) {
            return $this->taxonomy->getTermRepo()->getFlatTree($options['vocabulary']);
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
//            'choice_label' => 'hierarchyLabel',
            'choice_label' => $choiceLabel,
            'required'     => false,
            //'empty_value'  => '[None]',
            'attr'         => [
                'class' => 'term-parent-id'
            ],
            'term_label' => 'name',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
