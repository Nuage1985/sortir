<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminCampusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputAttr = 'bg-gray-800 border border-gray-600 text-heading text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2.5 placeholder:text-gray-400';
        $labelAttr = 'block mb-2.5 text-sm font-medium text-heading';

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Campus',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ]);

        if ($options['include_submit']) {
            $builder->add('save', SubmitType::class, [
                'label' => 'Ajouter',
                'attr' => ['class' => 'cursor-pointer font-medium text-blue-500 hover:underline bg-transparent border-0 p-0'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campus::class,
            'include_submit' => true,
        ]);
        $resolver->setAllowedTypes('include_submit', 'bool');
    }
}
