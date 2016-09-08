<?php
/**
 * Form type for ordering terms.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TermSortType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                'disabled' => true,
                'label' => false,
                'attr' => [
                    'class' => 'term-id',
                ],
            ])
            ->add('parent', TermEntityType::class, [
                'vocabulary' => $options['vocabulary'],
            ])
            ->add('path', TextType::class, [
                'attr' => [
                    'class' => 'term-path'
                ],
            ])
            ->add('weight', IntegerType::class, [
                'attr' => [
                    'class' => 'term-weight'
                ],
            ])
            ->add('level', IntegerType::class, [
                'attr' => [
                    'class' => 'term-level'
                ],
            ]);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'SymfonyContrib\\Bundle\\TaxonomyBundle\\Entity\\Term',
            'vocabulary' => null,
        ]);
    }
}
