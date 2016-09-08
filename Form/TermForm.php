<?php
/**
 * Form for creating/editing terms.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SymfonyContrib\Bundle\TaxonomyBundle\Form\Type\TermEntityType;

class TermForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'trim' => true,
            ])
            ->add('desc', TextareaType::class, [
                'required' => false,
                'trim'     => true,
            ])
            ->add('parent', TermEntityType::class, [
                'class'      => 'TaxonomyBundle:Term',
                'vocabulary' => $options['vocabulary'],
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-success',
                ]
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
            'cancel_url' => '/',
        ]);
    }
}
