<?php

namespace App\Form;

use App\Entity\Posts;
use App\Entity\ListPosts;
use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\Keyword;
use Doctrine\ORM\EntityRepository;
use App\Form\SubcategoryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PostsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('draft', CheckboxType::class, [
                'label' => 'Brouillon',
                'required' => false,
                'attr' => [
                    'class' => 'input mb-3',
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => false,
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => true,
            ]
                )
            ->add('subcategory', EntityType::class, [
                'label' => "Sous-catégorie de l'article",
                'class' => Subcategory::class,
                'choice_label' => 'name',
                'required' => false,
                'multiple' => false,
                'expanded' => true,
                ]
                )
            ->add('keywords', EntityType::class, [
                'class' => Keyword::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
                'by_reference' => false,
                ])
            ->add('heading', TextType::class, [
                'label' => 'Title ',
                'required' => true,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-lg text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Titre de l\'article* (max 65 caractères)',
                    'id' => 'post_contents',
                    'maxlength' => '65',
                    'minlength' => '35',
                    ]
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre H1 (Title H1 - Slug)',
                'required' => true,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-lg text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Titre de l\'article* (max 70 caractères)',
                    'id' => 'post_contents',
                    'maxlength' => '70',
                    ]
            ])
            ->add('metaDescription', TextType::class, [
                'label' => 'Meta description',
                'required' => true,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-lg text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Titre de l\'article* (max 70 caractères)',
                    'id' => 'post_contents',
                    'maxlength' => '135',
                    ]
            ])
            ->add('contents', TextareaType::class, [
                'label' => 'Paragraphe',
                'required' => true,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Paragraphe de l\'article* (max 5000 caractères) ',
                    'maxlength' => '5000',
                    'rows' => '4',
                    ]
            ])
            ->add('imgPost', FileType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'id' => 'image',
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
                'label' => 'Texte alternatif de l\'image',
                'required' => false,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-lg text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                    'placeholder' => 'Texte alternatif de l\'image (max 165 caractères)',
                    'maxlength' => '165',
                ]
            ])
            ->add('listPosts', CollectionType::class, [
                'entry_type' => ListPostsType::class,
                'required' => false,
                'label' => false,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('links', TextType::class, [
                'label' => 'Lien',
                'required' => false,
                'attr' => [
                    'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 list-input',
                    'placeholder' => 'ex: https://www.exemple.fr',
                    'maxlength' => '500',
                ]
                ])
            ->add('textLinks', TextType::class, [
                    'label' => 'Texte du lien',
                    'required' => false,
                    'attr' => [
                        'class' => 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 list-input',
                        'placeholder' => 'max 255 caractères',
                        'maxlength' => '255',
                    ]
                    ])
            ->add('isHomeImage', CheckboxType::class, [
                        'required' => false,
                        'label' => 'Définir comme image d\'accueil',
                    ])
            ->add('paragraphPosts', CollectionType::class, [
                'entry_type' => ParagraphPostsType::class,
                'label' => false,
                'required' => false,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('relatedPosts', EntityType::class, [
                        'class' => Posts::class,
                        'required' => false,
                        'choice_label' => 'title',
                        'expanded' => true,
                        'multiple' => true,
                        'by_reference' => false,
                        'attr' => [
                            'class' => 'custom-row',
                        ],
                        'query_builder' => function (EntityRepository $er) {
                                return $er->createQueryBuilder('p')
                                    ->where('p.draft IS NULL OR p.draft = false');
                            },


                    ])
            ;
                $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                    $form = $event->getForm();
                    $listPosts = $event->getData()->getParagraphPosts();
                });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Posts::class,
        ]);
    }
}
