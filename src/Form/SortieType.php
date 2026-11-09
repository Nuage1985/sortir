<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputAttr = 'bg-gray-800 border border-gray-600 text-heading text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2.5 placeholder:text-gray-400';
        $labelAttr = 'block mb-2.5 text-sm font-medium text-heading';
        $disabledInputAttr = 'bg-gray-700 border border-gray-600 text-gray-400 text-sm rounded-lg block w-full px-3 py-2.5 cursor-not-allowed';

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Titre de la sortie',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $disabledInputAttr],
                'disabled' => true,
            ])
            ->add('dateHeureDebut', null, [
                'label' => 'Date et heure de la sortie',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
                'widget' => 'single_text',
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr, 'min' => 1],
                'required' => true,
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => 'Date limite d\'inscription',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ])
            ->add('nbInscriptionMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr, 'rows' => 4],
            ])
            ->add('lieu', EntityType::class, [
                'label' => 'Lieu',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir un lieu --',
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'cursor-pointer text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-500 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none'],
            ])
            ->add('publier', SubmitType::class, [
                'label' => 'Publier',
                'attr' => ['class' => 'cursor-pointer text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-500 font-medium rounded-lg text-sm px-4 py-2.5 focus:outline-none'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
