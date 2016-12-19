<?php

namespace SymfonyContrib\Bundle\TaxonomyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use SymfonyContrib\Bundle\TaxonomyBundle\Entity\Repository\TermRepository;

/**
 * Tag style form field.
 *
 * Allows creating new terms.
 */
class TagType extends AbstractType
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tagOptions = [
            'vocabulary' => $options['vocabulary'],
            'multiple'   => true,
            'required'   => false,
            'label'      => $options['label'],
        ];
        if (!empty($options['choices'])) {
            $tagOptions['choices'] = $options['choices'];
        }
        $builder->add($builder->getName(), TermEntityType::class, $tagOptions);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form    = $event->getForm();
            $name    = $form->getName();
            $options = $form->getConfig()->getOptions();

            $data = $event->getData();
            if (empty($data) || empty($data[$name])) {
                return;
            }

            // Create unknown terms.
            $choices = [];
            foreach ($data[$name] as &$value) {
                if (is_numeric($value) && $term = $this->termRepo->find((int)$value)) {
                    $choices[] = $term;
                    continue;
                }
                $term      = $this->termRepo->createTerm($value, $options['vocabulary'], true);
                $value     = $term->getId();
                $choices[] = $term;
            }

            // Remove and re-ddd field to account for changed choices.
            $form->remove($name);
            $form->add($name, TermEntityType::class, [
                'vocabulary' => $options['vocabulary'],
                'multiple'   => true,
                'required'   => false,
                'choices'    => $choices,
            ]);

            $event->setData($data);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'vocabulary'   => 'tags',
            'label'        => false,
            'required'     => false,
            'inherit_data' => true,
            'choices'      => null,
            'attr'         => [],
        ]);

        $resolver->setRequired('vocabulary');

        $resolver->setNormalizer('attr', function (Options $options, $value) {
            $required = [
                'data-taxonomy-vocab'   => $options['vocabulary'],
                'data-taxonomy-tagging' => 'true',
            ];

            return array_merge($value, $required);
        });
    }
}
