<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
                'required' => false,
            ])
            ->add('mail', EmailType::class, [
                'label' => 'E-mail',
                'label_attr' => ['class' => $labelAttr],
                'attr' => ['class' => $inputAttr],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'Mot de passe', 'label_attr' => ['class' => $labelAttr], 'attr' => ['class' => $inputAttr]],
                'second_options' => ['label' => 'Confirmation du mot de passe', 'label_attr' => ['class' => $labelAttr], 'attr' => ['class' => $inputAttr]],
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
