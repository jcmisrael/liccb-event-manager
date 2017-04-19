<?php

namespace AppBundle\Controller;

use AppBundle\Form\EventEdit;
use AppBundle\Form\EventRegistrantsEdit;
use AppBundle\Form\EventScoring;
use AppBundle\Form\EventStrategies;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

function apply_strategy($answers, $strategy) {
		$reference = array(
			"over18W" => "over18",
			"swimExperienceW" => "swimExperience",
			"boatExperienceW" => "boatExperience",
			"cprW" => "cpr",
			"participantTypeW" => "participantType"
		);

		$score = floatval(0.0001);
		$weightSum = floatval(0.0001);

		
		foreach ($answers as $key => $value) {
			if ($value == $strategy[$key])
			{
				if($strategy[$key."W"] != -1)
				{
					$score+= floatval($strategy[$key."W"]);
				}
				$weightSum += floatval($strategy[$key."W"]);
			} else {
				if ($strategy[$key."W"] == -1)
				{
					$score = 0;
					break;
				}	
			}
		}
		
		$score = floatval($score/ $weightSum);
		return $score;
	}
	
class EventController extends Controller
{

    public function showAction(Request $request, $id)
    {
    	$event = $this->getDoctrine()
		    ->getRepository('AppBundle:Org_event')
		    ->find($id);
			
    	$registrantsForm = $this->createForm(EventRegistrantsEdit::class, $event);
	    $registrantsForm->handleRequest($request);
		
		/*
		$score_form = $this->createForm(EventScoring::class, $event, array(
			'action' => $this->generateUrl('event_score', array('id' => $id,)),
			'method' => 'POST',
		));
		
	    $score_form->handleRequest($request);
		*/
		
		$strategy_form = $this->createForm(EventStrategies::class, array(
			'action' => $this->generateUrl('event_strategy', array('id' =>$id,)),
			'method' => 'POST',
		));
		
		$strategy_form->handleRequest($request);
		/*
		$data = $strategy_form->getData();
		$strategy = $data['strategies'];
		$strategy_id_temp = $strategy->getId();
		*/

	    if($registrantsForm->isSubmitted() && $registrantsForm->isValid()){
	    	$event = $registrantsForm->getData();

		    foreach($event->getParties() as $party){
				
			    if($party->getSelectionStatus() == null){
			    	$party->setSelectionStatus("Emailed"); // Temporary hack
			    } elseif($registrantsForm->get('update_and_email')->isClicked() && $party->getSelectionStatus() == "Approved") {
			    	// Send email
				    $message = \Swift_Message::newInstance()
					    ->setSubject("LICBoathouse Event Approval")
					    ->setFrom('event_updates@licboathouse.org')
					    ->setTo($party->getRegistrantEmail())
					    ->setBody(
					    	$this->renderView('email/approved.html.twig', array(
					    		'name' => $party->getRegistrant()->getFullName(),
							    'event' => $event,
						    )),
						    'text/html'
					    )
					    ;
				    $this->get('mailer')->send($message);
			    	$party->setSelectionStatus("Emailed");
			    }
			}

	    	$em = $this->getDoctrine()->getManager();
	    	$em->persist($event);
	    	$em->flush();

	    	return $this->redirectToRoute('event_show', array(
	    		'id' => $id
		    ));
	    }
		
        return $this->render('event/show.html.twig', array(
	        'event' => $event,
	        'form' => $registrantsForm->createView(),
			'score_form' => $score_form->createView(),
			'strategy_form' => $strategy_form->createView(),
        ));
		
    }

	public function scoreAction(Request $request, $id)
	{
		$logger = $this->get('logger');
		$logger->info('test');
		
		// Retrieve the event data from the database
		$event = $this->getDoctrine()
			->getRepository('AppBundle:Org_event')
			->find($id);
			
		// create the registrants form, same as showAction
		$registrantsForm = $this->createForm(EventRegistrantsEdit::class, $event);
	    $registrantsForm->handleRequest($request);
		
		// create the scoreForm
    	$score_form = $this->createForm(EventScoring::class, $event, array(
			'action' => $this->generateUrl('event_score', array('id' => $id,)),
			'method' => 'POST',
		));
		
		// create 
	    $score_form->handleRequest($request);
		
		$strategy_form = $this->createForm(EventStrategies::class, array(
			'action' => $this->generateUrl('event_strategy', array('id' =>$id,)),
			'method' => 'POST',
		));
		$strategy_form->handleRequest($request);
		
		if($score_form->isSubmitted() && $score_form->isValid())
		{
	    	$event = $registrantsForm->getData();
			$strategy_data = $strategy_form->getData();
			$strategy = $strategy_data;
			//$logger->info($strategy_data['name']);
			/*
			** Trying to grab strategy from form in order to apply this strategy to scoring algorithm **
			
			$testStrategy1 = array(
				"id" => $strategy->getId(),
				"name" => $strategy->getName(),
				"over18" => $strategy->getOver18(),
				"over18W" => $strategy->getOver18W(),
				"swimExperience" => $strategy->getSwimExperience(),
				"swimExperienceW" => $strategy->getSwimExperienceW(),
				"boatExperience" => $strategy->getBoatExperience(),
				"boatExperienceW" => $strategy->getBoatExperienceW(),
				"cpr" => $strategy->getCpr(),
				"cprW" => $strategy->getCprW(),
				"participantType" => $strategy->getParticipantType(),
				"participantTypeW" => $strategy->getParticipantTypeW(),

			);
			*/
		
			foreach($event->getParties() as $party)
			{

					$registrant = $party->getRegistrant();
						$answers = array(
							"over18" => $registrant->getOver18(),
							"swimExperience" => $registrant->getHasSwimExperience(),
							"boatExperience" => $registrant->getHasBoatExperience(),
							"cpr" => $registrant->getHasCprCertification(), 
							"participantType" => $registrant->getParticipantType()
					);
					
					$testStrategy1 = array(
						"id" => 1,
						"name" => "Test Strategy 1",
						"over18" => true,
						"over18W" => -1,
						"swimExperience" => true,
						"swimExperienceW" => 10,
						"boatExperience" => true,
						"boatExperienceW" => 10,
						"cpr" => true,
						"cprW" => 10,
						"participantType" => "volunteer",
						"participantTypeW" => 10,
					);
		

					$score = apply_strategy($answers, $testStrategy1);	
					$logger->info('Blah');
					$logger->info($score);
					$party->setSelectionScore($score);	
			}

	    	$em = $this->getDoctrine()->getManager();
	    	$em->persist($event);
	    	$em->flush();			
		
			return $this->redirectToRoute('event_show', array(
				'id' => $id
			));
		}
		
		return $this->render('event/show.html.twig', array(
			'event' => $event,
			'form' => $registrantsForm->createView(),
			'score_form' => $score_form->createView(),
			'strategy_form' => $strategy_form->createView(),
        ));
		
	}
			
	public function strategyAction(Request $request, $id) {
		
		// grab event from DB
		$event = $this->getDoctrine()
			->getRepository('AppBundle:Org_event')
			->find($id);
		
		// grab strategies from DB
		$strategies = $this->getDoctrine()
			->getRepository('AppBundle:Strategy')
			->findAll();	
			
		// create the registrants form
		$registrantsForm = $this->createForm(EventRegistrantsEdit::class, $event);
	    $registrantsForm->handleRequest($request);
		
		
		/* Might not need score form at all
    	$score_form = $this->createForm(EventScoring::class, $event, array(
			'action' => $this->generateUrl('event_score', array('id' => $id,)),
			'method' => 'POST',
		));
	    $score_form->handleRequest($request);
		
		*/
		
		// create the strategy form
		$strategy_form = $this->createForm(EventStrategies::class, $event, array(
			'action' => $this->generateUrl('event_strategy', array('id' =>$id,)),
			'method' => 'POST',
		));
		$strategy_form->handleRequest($request);
		
		// This is all supposed to pre-populate the strategy data, doesn't work RN
		
		$data = $score_form->getData();
		$strategy = $data['strategies'];
			
		$strategy->setName(data['name']);
		
		$strategy->setOver18(data['over18']);
		$strategy->setOver18W(data['over18W']);
		if (data['over18Required'])
			$strategy->setOver18W(-1);
		
		$strategy->setSwimExperience(data['swimExpereince']);
		$strategy->setSwimExperienceW(data['swimExpereinceW']);
		if (data['over18Required'])
			$strategy->setSwimExperienceW(-1);
		
		$strategy->setBoatExpereince(data['boatExperience']);
		$strategy->setBoatExperienceW(data['boatExperienceW']);
		if (data['over18Required'])
			$strategy->setBoatExperienceW(-1);
		
		$strategy->setCpr(data['Cpr']);
		$strategy->setCprW(data['CprW']);
		if (data['over18Required'])
			$strategy->setCprW(-1);
		
		$strategy->setParticipantType(data['participantType']);
		$strategy->setParticipantTypeW(data['participantTypeW']);
		if (data['participantTypeRequired'])
			$strategy->setParticipantTypeW(-1);
		
		// End form pre-populate	
		
		// Check form is submitted
		if($strategy_form->isSubmitted() && $strategy_form->isValid())
		{
			$strategy = $strategy_form->getData();
		}
		
			$em = $this->getDoctrine()->getManager();
			$em->persist($strategy);
	    	$em->flush();	
		
		return $this->render('event/show.html.twig', array(
			'event' => $event,
			'form' => $registrantsForm->createView(),
			'score_form' => $score_form->createView(),
			'strategy_form' => $strategy_form->createView()
        ));
	}
    public function editAction(Request $request, $id)
	{   	
		$event = $this->getDoctrine()
		    ->getRepository('AppBundle:Org_event')
		    ->find($id);

    	$form = $this->createForm(EventEdit::class, $event);

    	$form->handleRequest($request);

    	if($form->isSubmitted() && $form->isValid()){
    		$event = $form->getData();

    		$em = $this->getDoctrine()->getManager();
    		$em->persist($event);
    		$em->flush();

    		return $this->redirectToRoute('event_show',array(
    			'id' => $id
		    ));
	    }

	    return $this->render('event/edit.html.twig', array(
	    	'form' => $form->createView(),
	    ));
    }
}
