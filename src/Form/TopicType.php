<?php

// src/Form/TopicType.php
namespace App\Form;

use App\Entity\Topic;
use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['class' => 'form-control'],
            ])
        ;
        
        // Add post content field when creating a new topic
        if (isset($options['post']) && $options['post'] instanceof Post) {
            $builder->add('content', TextareaType::class, [
                'label' => 'Message',
                'mapped' => false, // This field doesn't exist in the Topic entity
                'attr' => ['class' => 'form-control', 'rows' => 10],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Topic::class,
            'post' => null, // This allows us to pass a Post object to the form
        ]);
    }
}
