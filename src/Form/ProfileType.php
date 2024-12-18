<?php

namespace App\Form;

use App\Entity\Profile;
use App\Entity\Users;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
    
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Your profile picture (jpeg, png)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('birthday' , DateType::class, [
                'label' => 'Date of Birthday',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'] 
            ])
            ->add('country' , null, [
                'attr' => ['class' => 'form-control'] 
            ])
            ->add('address', null, [
                'attr' => ['class' => 'form-control'] 
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save profile',
                'attr' => ['class' => 'btn btn-primary'] 
            
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}