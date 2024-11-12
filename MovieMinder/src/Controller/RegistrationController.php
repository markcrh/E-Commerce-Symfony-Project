<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        //    dump($form->getData()); // Muestra los datos del formulario
        //      die(); // Detiene la ejecución aquí para inspeccionar los datos
            //encode the plain password
             $user->setPassword(
                 $userPasswordHasher->hashPassword(
                     $user,
                     $form->get('plainPassword')->getData()
                 )
                );
                $entityManager->persist($user);
                $entityManager->flush();  

            //  try {
            //  } catch (\Exception $e) {
                
            //      $this->addFlash('error', 'Hubo un error al registrar al usuario. Intenta de nuevo.');
            //      return $this->redirectToRoute('app_register');
            //  }
             // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                 $user,
                 $authenticator,
                 $request
             );

            
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
