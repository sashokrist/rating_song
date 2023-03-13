<?php

namespace App\Form;

use App\Entity\Song;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SongFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('band', TextType::class, [
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Enter band name'
                )
            ])
            ->add('song_name', TextType::class, [
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Enter song title'
                )
            ])
            ->add('image', FileType::class, [
                'attr' => array(
                    'class' => 'form-control',
                )
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Song::class,
        ]);
    }
}
