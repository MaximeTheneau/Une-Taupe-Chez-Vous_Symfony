<?php

namespace App\Form;

use App\Entity\ParagraphPosts;
use App\Entity\Posts;
use App\Repository\PostsRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class ParagraphPostsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('subtitle', TextType::class, [
                    'label' => 'Sous-titre',
                    'required' => true,
                    'attr' => [
                        'class' => 'block p-2.5 w-full text-xl text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Sous-titre du paragraphe (max 170 caractères)',
                    'maxlength' => '170',
                    ]
                ])

            ->add('paragraph', TextareaType::class, [
                    'label' => 'Paragraphe',
                    'attr' => [
                        'class' => 'block p-2.5 w-full text-xl text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                        'placeholder' => 'Paragraphe de l\'article (max 5000 caractères)',
                        'maxlength' => '5000',
                        'rows' => '7',
                        ]
                    ])

                    ->add('imgPostParaghFile', FileType::class, [
                        'label' => 'Image du paragraphe',
                        'required' => false,
                        'data_class' => null,
                        'attr' => [
                            'placeholder' => 'max 5Mo',
                            'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                        ],
                // 'constraints' => [
                //     new File([
                //         'maxSize' => '5M',
                //         'mimeTypes' => [
                //             'image/jpeg',
                //             'image/webp',
                //             'image/png',
                //         ],
                //         'mimeTypesMessage' => 'Veuillez uploader une image valide',
                //     ])
                // ],
            ],)
            ->add('altImg', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'block p-2.5 w-full text-lg text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                        'placeholder' => 'Texte alternatif de l\'image (max 165 caractères)',
                        'maxlength' => '165',
                    ]
                ])

            ->add('linkSubtitle', TextType::class, [
                    'label' => 'Sous-titre du lien',
                    'required' => false,
                    'attr' => [
                        'class' => 'font-black block p-2.5 w-full text-lg  bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                        'placeholder' => 'Sous-titre du lien (max 255 caractères)',
                        'maxlength' => '255',
                        ]
            ])
            ->add('linkPostSelect', EntityType::class, [
                    'class' => Posts::class,
                    'choice_label' => 'title',
                    'label' => 'Lien vers un autre article',
                    'required' => false,
                    'choice_value' => 'id',
                    'placeholder' => 'Choisir un article',
                    'attr' => [
                        'class' => 'font-bold'
                        ]
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $e) {
                $form = $e->getForm();
                $recipe = $e->getData();

                if ($recipe && $recipe->getId()) {
                    $form->add('deleteLink', ChoiceType::class, [
                        'mapped' => false,
                        'label' => "Supprimer le lien ?",
                        'choices' => [
                            "oui" => true,
                            "non" => false
                        ],
                        'expanded' => true,
                    ]);
                }
            })
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParagraphPosts::class,
        ]);
    }

}
