<?php
namespace App\Form;

use App\Entity\Courses;
use App\Entity\Lessons;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class LessonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('course', EntityType::class, [
                'class' => Courses::class,
                'choice_label' => 'title',
                'label' => 'Select Course', // Label for the course field
                'attr' => ['class' => 'form-control mb-3'], // Adding Bootstrap class for styling
            ])
            ->add('title', null, [
                'label' => 'Lesson Title', // Label for the title field
                'attr' => ['class' => 'form-control mb-3'], // Adding Bootstrap class for styling
            ])
            ->add('content', CKEditorType::class, [
                'config_name' => 'my_custom_config',
                'label' => 'Lesson Content', // Label for the content field
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Course PDF File (PDF)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Lesson', // Label for the submit button
                'attr' => ['class' => 'btn btn-primary'], // Adding Bootstrap class for styling
            ])
           ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lessons::class,
        ]);
    }
}
