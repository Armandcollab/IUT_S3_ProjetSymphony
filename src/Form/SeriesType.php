<?php

namespace App\Form;

use App\Entity\Series;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('plot',TextType::class)
            ->add('imdb')
            ->add('poster') //TODO ajouter un poster en binaire
            ->add('director')
            ->add('youtubeTrailer', UrlType::class)
            ->add('awards', TextType::class)
            ->add('yearStart')
            ->add('yearEnd')
            ->add('actor')
            ->add('country', null, [ 
                'expanded' => true
            ])
            ->add('genre', null, [ 
                'expanded' => true
            ])
            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Series::class,
        ]);
    }
}
