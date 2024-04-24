<?php

namespace App\Form;

use App\Entity\Courses;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
class CoursesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
            'label' => 'Course picture (jpeg, png)',
            'mapped' => false,
            'required' => false,
            'attr' => ['class' => 'form-control']
        ])
            ->add('title'  , TextType::class, [
                'label' => 'Course Title',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description' , TextType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control']
            ])
            ->add('hours', IntegerType::class, [
                'label' => 'Hours',
                'attr' => ['class' => 'form-control']])
            ->add('minutes', IntegerType::class, [
                'label' => 'Minutes',
                'attr' => ['class' => 'form-control']
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Course PDF File (PDF)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Courses::class,
        ]);
    }
}
