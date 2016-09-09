<?php
/**
 * Taxonomy entity type form field.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

        $resolver->setDefaults([
            'class'        => 'TaxonomyBundle:Term',
            'vocabulary'   => null,
            'choices'      => $choiceList,
            'choice_label' => 'hierarchyLabel',
            'required'     => false,
            //'empty_value'  => '[None]',
            'attr'         => [
                'class' => 'term-parent-id'
            ],
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
