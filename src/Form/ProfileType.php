<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;

class ProfileType extends ParticipantBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Héritage
        parent::buildForm($builder, $options);

        $inputAttr = 'bg-gray-800 border border-gray-600 text-heading text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2.5 placeholder:text-gray-400';
        $labelAttr = 'block mb-2.5 text-sm font-medium text-heading';
        $disabledInputAttr = 'bg-gray-700 border border-gray-600 text-gray-400 text-sm rounded-lg block w-full px-3 py-2.5 cursor-not-allowed';

        $builder
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'required' => false,
                'disabled' => true,
                'label' => 'Campus',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $disabledInputAttr],
            ])
            ->add('image', FileType::class, [
                'label' => 'Photo de profil',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '1M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Merci de choisir une image valide (jpeg, png, jpg), taille maximum 1Mo',
                    ])
                ],
            ])
        ;
    }
}
