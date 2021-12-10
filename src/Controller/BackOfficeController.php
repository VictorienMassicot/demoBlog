<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BackOfficeController extends AbstractController
{
    // méthode qui affiche la page home du back office
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }

    #[Route('/admin/article', name: 'bo_article')]
    #[Route('/admin/article/{id}/remove', name: 'bo_article_remove')]
    public function adminArticle(EntityManagerInterface $manager, ArticleRepository $repoArticles, Article $artRemove = null): Response
    {
        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);

        $articles = $repoArticles->findAll();
        // dd($articles);

        // Traitement suppression article BDD
        if($artRemove)
        {
            // Avant de supprimer l'article dans la BDD, on stock son ID afin de l'intégrer dans le message de validation de suppressions
            $id = $artRemove->getId();

            $manager->remove($artRemove);
            $manager->flush();

            $this->addFlash('success', "L'article $id a été supprimé avec succès.");

            return $this->redirectToRoute('bo_article');
        }

        /*
            Exo : Afficher sous forme de tableau HTML l'ensemble des articles stocké dans en BDD
            1. Selectionner en BDD l'ensemble de la table 'article' et transmettre le résultat à la méthode render()
            2. Dans le template 'admin_articles.html.twig' mettre en forme l'affichage des articles
            3. Afficher le nom de la catégorie de chaque article
            4. Afficher le nombre de commentaire de chaque article
            5. Prévoir un lien modification / suppression pour chaque article
        */

        return $this->render('back_office/admin_articles.html.twig', [
            'colonnes' => $colonnes,
            'articles' => $articles
        ]);
    }

    #[Route('/admin/users', name: 'bo_users')]
    public function adminUsers(UserRepository $repoUser, EntityManagerInterface $manager): Response
    {
        $colonnes = $manager->GetClassMetadata(User::class)->getFieldNames();
        // dd($colonnes);
        
        $user = $repoUser->findAll();
        // dd($user);

        return $this->render('back_office/admin_users.html.twig', [
            'colonnes' => $colonnes,
            'utilisateurs' => $user
        ]);
    }

    /*
        Exo: création d'une nouvelle méthode permettant d'insérer et de modifier 1 article en BDD
        // 1. créer une route 'admin/article/add'
        // 2. créer la méthode adminArticleForm()
        // 3. créer un nouveau template 'admin_article_form.html.twig'
        // 4. Importer et créer le formulaire au sein de la méthode adminArticleForm
        // 5. afficher le formulaire sur le template 'admin_article_form.html.twig'
        // 6. Dans la méthode adminArticleForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD
    */

    #[Route('admin/article/add', name: 'bo_add_article')]
    #[Route('admin/article/{id}/update', name: 'bo_update_article')]
    public function adminArticleForm(Request $request, EntityManagerInterface $manager, Article $article = null, SluggerInterface $slugger): Response
    {
        if($article)
        {
            $photoActuelle = $article->getPhoto();
        }

        if(!$article)
        {
            $article = new Article;
        }


        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        // dd($article);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {

            if(!$article->getId())
                $article->setDate(new \DateTime());


            $photo = $formArticle->get('photo')->getData();

            if($photo) 
            {
                $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

                $secureNomPhoto =  $slugger->slug($nomOriginePhoto);

                $nvNomFichier = $secureNomPhoto . '-' . uniqid() . '.' . $photo->guessExtension();

                try
                {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $nvNomFichier
                    );
                }
                catch(FileException $e)
                {

                }

                $article->setPhoto($nvNomFichier);
            }
            else
            {
                if(isset($photoActuelle))
                    $article->setPhoto($photoActuelle);
                else
                    $article->setPhoto(null);
            }

            if(!$article->getId())
                $txt = "ajouté";
            else
                $txt = "modifié";

            $this->addFlash('success', "L'article a été $txt avec succès");

            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('bo_article', [
                'id' => $article->getId()
            ]);
        } 

        return $this->render('back_office/admin_article_form.html.twig', [
            'form_article' => $formArticle->createView(),
            'editMode' => $article->getId(),
            'photoActuelle' => $article->getPhoto()
        ]);
    }

    /*
        Exo: affichage et suppresion catégorie
        // 1. Création d'une nouvelle route '/admin/categories'
        // 2. création d'une nouvelle méthode adminCategories()
        // 3. création d'un nouveau template 'admin_categories.html.twig
        // 4. selectionner les noms des champs/colonnes de la table Category, les transmettre au template et les afficher
        // 5. selectionner dans le controller l'ensemble de la table 'category' (findAll) et les transmetrte au template, afficher également le nombre d'articles liés à chaque catégorie
        // 6. prévoir un lien 'modifier' et 'supprimer' pour chaque catégorie
        // 7. Réaliser le traitement permettant de supprimer une catégorie de la BDD
    */

    #[Route('/admin/categories', name: 'bo_category')]
    #[Route('/admin/categories/{id}/remove', name: 'bo_category_remove')]
    public function adminCategories(CategoryRepository $repoCategory, EntityManagerInterface $manager, Category $catRemove = null): Response
    {
        $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

        $catInfo = $repoCategory->findAll();

        // dd($catInfo);

        if($catRemove)
        {
            $id = $catRemove->getId();

            $manager->remove($catRemove);
            $manager->flush();

            $this->addFlash('success', "La catégorie $id a été supprimé avec succès.");

            return $this->redirectToRoute('bo_category');
        }

        return $this->render('back_office/admin_category.html.twig', [
            'category' => $catInfo,
            'colonnes' => $colonnes
        ]);
    }
}