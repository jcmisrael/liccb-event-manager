<?php

namespace AppBundle\Form;

use AppBundle\Entity\Party;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartyAttendanceEdit extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	    $builder
			->add('numActuallyAttended', IntegerType::class, array(
				'label' => false,
                /* 'attr' => array('min' => 0)  min/max moved to twig page */
                ))
            ->add('thumbs', ChoiceType::class, array(
                'label' => false,
                'required' => false,
                'choices' => array(
                    '' => "",
                    'Up' => "Up",
                    'Down' => "Down",
                )
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
	    $resolver->setDefaults(array(
	    	'data_class' => Party::class,
	    ));
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_party_attendance_edit';
    }
}
