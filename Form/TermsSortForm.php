<?php
/**
 * Form for ordering/sorting terms in a hierarchy.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\Type\TermSortType;

class TermsSortForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('terms', CollectionType::class, [
                'entry_type'    => TermSortType::class,
                'label'         => false,
                'entry_options' => [
                    'vocabulary' => $options['vocabulary'],
                ],
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-success',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cancel_url' => '/',
            'vocabulary' => null,
        ]);
    }
}
