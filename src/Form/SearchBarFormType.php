<?php

namespace App\Form;

use App\Entity\Series;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;


class SearchBarFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'required'=>false,
                'mapped'=> false,
            ])
            ->add('note', CheckboxType::class, [
                'label' => 'par note',
                'required' => false,
                'mapped' => false,
            ])
            ->add('decroissant', CheckboxType::class, [
                'label' => 'décroissant',
                'required' => false,
                'mapped' => false,
            ]);
    }
}
