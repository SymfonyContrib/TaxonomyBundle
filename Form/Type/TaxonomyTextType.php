<?php
/**
 * Taxonomy text type form field.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'vocabulary' => null,
            'multiple'   => true,
            'delimiter'  => ',',
            'enclosure'  => '"',
            'compound'   => false,
            'attr'       => [
                'class' => 'term-single-select',
            ],
       ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}
