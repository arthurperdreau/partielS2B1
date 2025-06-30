<?php

namespace App\Form;

use App\Entity\Film;
use App\Entity\Horaire;
use App\Entity\Salle;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeanceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date')
            ->add('film', EntityType::class, [
                'class' => Film::class,
                'choice_label' => 'name',
            ])
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => 'id',
            ])
            ->add('horaire', EntityType::class, [
                'class' => Horaire::class,
                'choice_label' => function(Horaire $horaire) {
                    return $horaire->getHoraire()->format('H:i'); // ex: "09:30"
                },
            ])

            ->add('version', ChoiceType::class, [
                'choices'  => [
                    'Version Française (VF)' => 'VF',
                    'Version Originale (VO)' => 'VO',
                ],
                'expanded' => false,
                'multiple' => false,
                'label' => 'Version de la séance',])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
        ]);
    }
}
