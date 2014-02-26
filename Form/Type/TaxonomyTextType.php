<?php
/**
 * Taxonomy text type form field.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\DataTransformer\TermsToCsvTransformer;
use SymfonyContrib\Bundle\TaxonomyBundle\Taxonomy;

class TaxonomyTextType extends AbstractType
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['taxonomy'] = $this->taxonomy;
        $builder->addModelTransformer(new TermsToCsvTransformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'vocabulary' => null,
            'multiple' => true,
            'delimiter' => ',',
            'enclosure' => '"',
            'compound' => false,
            'attr' => [
                'class' => 'term-single-select',
            ]
       ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'taxonomy_text';
    }
}
