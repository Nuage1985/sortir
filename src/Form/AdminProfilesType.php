<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminProfilesType extends ParticipantBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Héritage
        parent::buildForm($builder, $options);

        if ($options['include_actif']) {
            $builder
                ->add('actif', CheckboxType::class, [
                    'label' => 'Actif',
                    'label_attr' => ['class' => 'text-heading font-medium text-sm'],
                    'attr' => ['class' => 'h-4 w-4 rounded border-gray-600 bg-gray-800 text-blue-600 focus:ring-blue-500 focus:ring-offset-github-dark-gray'],
                    'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'include_actif' => true,
        ]);
    }
}
