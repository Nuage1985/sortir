<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ImportCsvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputAttr = 'bg-gray-800 border border-gray-600 text-heading text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2.5 placeholder:text-gray-400';
        $labelAttr = 'block mb-2.5 text-sm font-medium text-heading';

        $builder->add('fichier', FileType::class, [
            'label' => 'Fichier CSV',
            'label_attr' => ['class' => $labelAttr],
            'attr' => ['class' => $inputAttr],
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File(
                    maxSize: '2M',
                    mimeTypes: ['text/csv', 'text/plain', 'application/vnd.ms-excel'],
                    mimeTypesMessage: 'Merci de fournir un fichier CSV valide',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
