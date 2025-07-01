<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Seance;
use App\Entity\Siege;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
            $builder
                ->add('sieges', EntityType::class, [
                    'class' => Siege::class,
                    'multiple' => true,
                    'expanded' => true,
                    'choice_label' => 'numero',
                    'label' => 'Sélectionnez vos sièges'
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);

    }
}
