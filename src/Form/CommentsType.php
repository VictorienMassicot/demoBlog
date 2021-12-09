<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commentaire', TextareaType::class, [
                'label' => "Commentaire",
                'attr' => [
                    'placeholder' => "Ecrivez quelque chose...",
                ],
                'constraints' => [
                    new Length([
                        'max' => 450,
                        'maxMessage' => "Votre commentaire dépasse la limite de 450 caractères"
                    ]),
                    new NotBlank([
                        'message' => "Merci de saisir votre nom."
                    ])
                ]
            ])
            ->add('auteur', TextType::class, [
                'label' => 'Pseudo',
                'attr' => [
                    'placeholder' => "Saisissez votre pseudonyme"
                ],
                'constraints' => [
                    new Length([
                        'min' => 4,
                        'max' => 16,
                        'minMessage' => "Votre pseudo est trop court (min 4 caractères)",
                        'maxMessage' => "Votre pseudo est trop long (max 16 caractères)"
                    ]),
                    new NotBlank([
                        'message' => "Merci de saisir votre commentaire."
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}