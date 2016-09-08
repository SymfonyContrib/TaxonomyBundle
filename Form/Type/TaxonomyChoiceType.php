<?php
/**
 * Taxonomy choice type form field.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use SymfonyContrib\Bundle\TaxonomyBundle\Taxonomy;

class TaxonomyChoiceType extends AbstractType
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
    /*public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $terms = $this->taxonomy->getTermRepo()->getFlatTree($options['vocabulary'], $builder->getName());
        dump($terms);
        $builder->add($builder->getName(), ChoiceType::class, [
            'choices'  => $terms,
            'expanded' => true,
            'multiple' => true,
            'label'    => false,
        ]);
    }*/

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $terms = function (Options $options) {
            return array_values($this->taxonomy->getTermRepo()->getFlatTree($options['vocabulary'], $options['field']));
        };

        $resolver->setDefaults([
            'inherit_data' => true,
            'vocabulary'   => null,
            'field'        => null,
            'choices'      => $terms,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
