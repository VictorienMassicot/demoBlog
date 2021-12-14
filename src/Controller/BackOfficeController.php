<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Commentaire;
use App\Form\ArticleType;
use App\Form\CategoryType;
use App\Form\CommentsType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentaireRepository;
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

    #[Route('/admin/users/{id}/edit', name: 'bo_users_edit')]
    #[Route('/admin/users/{id}/remove', name: 'bo_users_remove')]
    public function editRoleUser(EntityManagerInterface $manager, User $utilisateurs, Request $request): Response
    {
        $formEditUser = $this->createForm(RegistrationFormType::class, $utilisateurs, [
            'userBack' => true
        ]);

        $formEditUser->handleRequest($request);
        // dd($formEditUser);

        if($formEditUser->isSubmitted() && $formEditUser->isValid())
        {
            $id = $utilisateurs->getId();

            $this->addFlash('success', "L'utilisateur $id a été modifié avec succès");

            $manager->persist($utilisateurs);
            $manager->flush();

            return $this->redirectToRoute('bo_users');
        }
        else // sinon, aucun paramètres dans l'URL, alors on execute une requete de suppression
        {
            $infos = $utilisateurs->getPrenom() . " " . $utilisateurs->getNom();

            $manager->remove($utilisateurs);
            $manager->flush();

            $this->addFlash('success', "Le rôle de l'utilisateur $infos a été supprimé avec succès.");

            return $this->redirectToRoute('bo_users');
        }


        return $this->render('back_office/admin_user_edit.html.twig', [
            'formEditUser' => $formEditUser->createView()
        ]);
    }

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

    #[Route('/admin/categories', name: 'bo_category')]
    #[Route('/admin/categories/{id}/remove', name: 'bo_category_remove')]
    public function adminCategories(CategoryRepository $repoCategory, EntityManagerInterface $manager, Category $catRemove = null): Response
    {
        $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

        $catInfo = $repoCategory->findAll();

        // dd($catInfo);

        if($catRemove) // ajouter le if si on entre dans la route 'remove'
    {
        $titreCat = $catRemove->getTitre();

        // getArticles() retourne tous les articles liés à la catégorie, si le résultat est vide, cela veut dire qu'aucun article n'est associé à cette catégorie. On entre dans le IF et on supprime la catégorie
        if($catRemove->getArticles()->isEmpty())
        {
            $manager->remove($catRemove);
            $manager->flush();
            
            $this->addFlash('success', "La catégorie '$titreCat' a été supprimée avec succès");

            return $this->redirectToRoute('bo_category');
        }
        else // sinon, des articles sont encore liés à la catégorie, alors on affiche un message d'erreur à l'utilisateur
        {
            $this->addFlash('danger', "Impossible de supprimer la catégorie '$titreCat' car des articles y sont toujours associés");
        }
        
    }

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

    #[Route('admin/categorie/add', name: 'bo_category_add')]
    #[Route('admin/categorie/{id}/edit', name: 'bo_category_edit')] // ajouter la path() dans la liste des catégories
    public function adminCategorieForm(Request $request, EntityManagerInterface $manager, Category $category = null): Response
    {
        if(!$category)
            $category = New Category;

        $formCategory = $this->createForm(CategoryType::class, $category);

        $formCategory->handleRequest($request);

        if($formCategory->isSubmitted() && $formCategory->isValid())
        {
            if($category->getId())
                $txt = 'modifiée';
            else
                $txt = 'enregistrée';

            $manager->persist($category);
            $manager->flush();

            // on stock le titre de la catégorie dans une variable afin de l'intégrer dans le message de validation
            $titreCat = $category->getTitre();

            $this->addFlash('success', "La catégorie '$titreCat' a été $txt avec succés.");

            return $this->redirectToRoute('bo_category'); // mettre la route de l'affichage des catégories
        }

        return $this->render('back_office/admin_categorie_form.html.twig', [
            'formCategory' => $formCategory->createView(),
            'editMode' => $category->getId()
        ]);
    }

    #[Route('/admin/commentaires', name:'bo_comments')]
    #[Route('/admin/commentaires/{id}/remove', name: 'bo_comments_remove')]
    public function adminCommentaires(CommentaireRepository $repoComments, EntityManagerInterface $manager, Commentaire $commentaire = null): Response
    {
        $colonnes = $manager->getClassMetadata(Commentaire::class)->getFieldNames();
        // dd($colonnes);

        $comments = $repoComments->findAll();
        // dd($comments);

        if($commentaire)
        {
            $id = $commentaire->getId();

            $manager->remove($commentaire);
            $manager->flush();

            $this->addFlash('success', "Le commentaire $id a été supprimé avec succès.");

            return $this->redirectToRoute('bo_comments');
        }

        return $this->render('back_office/admin_commentaires.html.twig', [
            'comments' => $comments,
            'colonnes' => $colonnes,
        ]);
    }

    #[Route('/admin/commentaires/{id}/edit', name: 'bo_comments_edit')]
    public function commentaireEdit(Commentaire $commentaire, Request $request): Response
    {
        $formComments = $this->createForm(CommentsType::class, $commentaire, [
            'commentFormBack' => true
        ]);

        $formComments->handleRequest($request);
        // dd($formComments);

        return $this->render('back_office/admin_commentaires_form.html.twig', [
            'formComments' => $formComments->createView()
        ]);
    }
}