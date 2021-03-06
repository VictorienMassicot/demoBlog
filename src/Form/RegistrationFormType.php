<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if($options['userRegistration'] == true)
        {
            $builder
                ->add('email', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre email."
                        ]),
                    ]
                ])
                ->add('prenom', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre prenom."
                        ]),
                    ]
                ])
                ->add('nom', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre nom."
                        ]),
                    ]
                ])
                ->add('adresse', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre adresse."
                        ]),
                    ]
                ])
                ->add('ville', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre ville."
                        ]),
                    ]
                ])
                ->add('codePostal', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre code postal."
                        ]),
                    ]
                ])
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'required' => false,
                    'invalid_message' => "Les mots de passes ne correspondent pas",
                    'options' => [
                        'attr' => [
                            'class' => 'password-field'
                        ]
                    ],
                    'first_options' => [
                        'label' => "Mot de passe"
                    ],
                    'second_options' => [
                        'label' => "Confirmer votre mot de passe"
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre mot de passe."
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => "Votre mot de passe doit contenir au minimum 8 caract??res."
                        ])
                    ]
                ])
            ;
        }
        elseif($options['userUpdate'] == true)
        {
            $builder
                ->add('email', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre email."
                        ]),
                    ]
                ])
                ->add('prenom', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre prenom."
                        ]),
                    ]
                ])
                ->add('nom', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre nom."
                        ]),
                    ]
                ])
                ->add('adresse', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre adresse."
                        ]),
                    ]
                ])
                ->add('ville', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre ville."
                        ]),
                    ]
                ])
                ->add('codePostal', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new NotBlank([
                            'message' => "Veuillez renseigner votre code postal."
                        ]),
                    ]
                ])
            ;
        }
        elseif($options['userBack'] == true)
        {
            // Nous definissons le champs 'roles' en CollectionType afin de renvoyer dans la bdd un array
            // ChoiceType permet de d??finir une liste d??roulante
            // entry_options permet de d??finir les options de selecteurs
            $builder
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'utilisateur' => '',
                    'administrateur' => 'ROLE_ADMIN'
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => "D??finir le role de l'utilisateur"
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'userRegistration' => false,
            'userUpdate' => false,
            'userBack' => false
        ]);
    }
}
