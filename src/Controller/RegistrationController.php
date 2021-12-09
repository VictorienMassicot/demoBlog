<?php

namespace App\Controller;


use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\PseudoTypes\True_;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Si getUser() renvoi des données, ça veut dire que l'internaute est authentifié donc inscrit, il n'a rien à faire sur la route '/register', on le redirige vers la route du blog '/blog'
        if($this->getUser())
        {
            return $this->redirectToRoute('blog');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'userRegistration' => true // on précise dans quelle condition on entre dans la classe RegistrationFormType pour afficher un formulaire en particulier, la classe contient plusieurs formulaire
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // on fait appel à l'objet $userPasswordHasher() de l'interface UserPasswordHasherInterface afin d'encoder le mot de passe en BDD
            // en argument on lui fournit l'objet entité dans lequel nous allons encoder un élément ($user) et on lui fournit le mot de passe saisi dans le formulaire a encoder
            $hash = $userPasswordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );

            // dd($hash);

            $user->setPassword($hash);

            $this->addFlash('success', "Félicitations, vous êtes maintenant inscrit sur le site");
        
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $this->redirectToRoute('home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /*
        Exo : créer une page profil affichant les données de l'utilisateur identifié
        1. créer une nouvelle route '/profil'
        2. créer une nouvelle méthode userProfil()
        3. cette méthode renvoi un template 'registration/profil.html.twig'
        4. afficher dans ce template les informations de l'utilisateur connecté
    */

    #[Route('/profil', name:'app_profil')]
    public function userProfil()
    {
        // Si getUser() est null, et ne renvoi aucune données, cela veut dire que l'internaute n'est pas authentifié, il n'a rien à faire sur le route '/profil', on le redirige vers la route de connexion '/login'
        if(!$this->getUser())
        {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        // dd($user);

        return $this->render('registration/profil.html.twig', [
            'user' => $user
        ]);
    }

    // Méthode permettant de modifier les infos utilisateurs en BDD (sauf MDP)
    #[Route('/profil/{id}/edit', name: 'app_profil_edit')]
    public function userProfilEdit(User $user, Request $request, EntityManagerInterface $manager): Response
    {
        // dd($user);

        $formUpdate = $this->createForm(RegistrationFormType::class, $user, [
            'userUpdate' => true
        ]);

        // $user->setPrenom($_POST['prenom'])
        $formUpdate->handleRequest($request);

        if($formUpdate->isSubmitted() && $formUpdate->isValid())
        {
            // dd($user);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash('succes', "Vous avez modifié vos informations, merci de vous identifié à nouveau");

            // une fois que l'utilisateur a modifié ses informations de profil, on le redirige vers la route deconnexion, on le deconnecte pour qu'il puisse après mettre à jour la session en s'authentifiant de nouveau
            return $this->redirectToRoute('app_logout');
        }

        return $this->render('registration/profil_edit.html.twig', [
            'formUpdate' => $formUpdate->createView()
        ]);
    }
}