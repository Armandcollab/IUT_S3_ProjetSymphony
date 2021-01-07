<?php

namespace App\Form;

use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\IsTrue;


class RatingFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $time = new \DateTime();
        $builder
            ->add('value', ChoiceType::class, array(
                'choices' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ),
                'mapped' => true,
                'label' => false,
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de rentrer un commentaire',
                    ]),
                ],
                'expanded' => true
            ))
            ->add('comment',  TextareaType::class, array(
                'mapped' => true,
                'required' => true,
                'attr' => array('style' => 'width: 600px;height:250px;resize: none'),

            ))
            ->add('date',  DateTimeType::class, array(
                'mapped' => true,
                'required' => true,
                'data' => $time,
                'label' => false,
                'attr' => array('style' => 'visibility:hidden;')
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Envoyer',
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
