<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class VolunteersController extends Controller
{
    public function addAction(Request $request)
    {

    	$data = array();

    	$form = $this->createFormBuilder($data)
		    ->add('email', EmailType::class, array(
		    	'label' => 'Email of Volunteer: '
		    ))
		    ->add('submit', SubmitType::class)
		    ->getForm();

    	$form->handleRequest($request);

    	if($form->isSubmitted()){
    		$data = $form->getData();

    		$user_manager = $this->get('fos_user.user_manager');
		    $flashbag = $request->getSession()->getFlashBag();
    		$email = $data['email'];

    		$user = $user_manager->findUserByEmail($email);
    		if($user instanceof UserInterface){
    			$flashbag->add('error', "Volunteer with email, " . $email . ', already exists!');
		    } else {
			    $user = $user_manager->createUser();
			    $user->setEmail($email);

			    $encoder = $this->get('security.password_encoder');
			    $encoded = $encoder->encodePassword($user, uniqid());
			    $user->setPlainPassword($encoded);
			    $user->setEnabled(true);
			    $user->addRole('ROLE_USER');

			    $user_manager->updateUser($user);

			    $flashbag->add('success', 'The volunteer, ' . $email . ', was registered to the website!');
		    }
    		return $this->redirectToRoute('admin_volunteers_add');
	    }

        return $this->render('admin/volunteers/add.html.twig', array(
        	'form' => $form->createView(),
        ));
    }

    public function editGroupsAction(Request $request, $user_id){
	    if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
		    throw $this->createAccessDeniedException();
	    }

	    $flashbag = $request->getSession()->getFlashBag();

	    /* @var $user User */
	    $user_manager = $this->get('fos_user.user_manager');
	    $user = $user_manager->findUserBy(array('id'=>$user_id));

	    $form = null;
	    if($user) {
		    $form = $this->createFormBuilder($user)
			    ->add('groups', EntityType::class, array(
				    'class' => 'AppBundle\Entity\Group',
				    'choice_label' => 'name',
				    'expanded' => true,
				    'multiple' => true,
				    'label' => false,
			    ))
			    ->add('Submit', SubmitType::class)
			    ->getForm();

		    $form->handleRequest($request);
		    if ($form->isSubmitted()) {
			    $user = $form->getData();

			    $user_manager->updateUser($user);

			    $flashbag->add('success', 'The volunteer, ' . $user->getEmail() . ', groups have been updated');
			    return $this->redirectToRoute('admin_volunteers_show', array(
			    	'user_id' => $user_id
			    ));
		    }
	    } else {
	    	$flashbag->add('error', 'No volunteer with matching id found');
	    }

	    return $this->render('admin/volunteers/editGroups.html.twig', array(
	    	'user' => $user,
		    'form' => $form->createView()
	    ));
    }

    public function showAction(Request $request, $user_id){
    	$user_manager = $this->get('fos_user.user_manager');
    	$user = $user_manager->findUserBy(array('id'=>$user_id));

    	$flashbag = $request->getSession()->getFlashBag();

    	if(!$user){
    		$flashbag->add('error', 'No volunteer matching given id could be found');
	    }

	    return $this->render('admin/volunteers/show.html.twig', array(
	    	'user' => $user
	    ));
    }
}
