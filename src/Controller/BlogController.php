<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Form\CommentsType;
use App\Entity\Commentaire;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BlogController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // méthode rendu, en fonction de la route dans l'URL, la méthode render() envoi un template, un rendu, sur le navigateur
        return $this->render('blog/home.html.twig', [
            'title' => 'Bienvenue sur le blog Symfony',
            'age' => 25
        ]);
    }

    // cette méthode permet de selectionner toute les catégories de la BDD amis ne possède pas de route, les catégories seront intégrées dans base.html.twig
   public function allCategory(CategoryRepository $repoCategory)
    {
        $categorys = $repoCategory->findAll();

        return $this->render('blog/categorys_list.html.twig', [
            'categorys' => $categorys
        ]);
    }

    #[Route('/blog', name: 'blog')]
    #[Route('/blog/categorie/{id}', name: 'blog_categorie')]
    public function blog(ArticleRepository $repoArticle): Response
    {
        /*
            Injections de dépendances : c'est un fondement de Symfony, ici notre méthode DEPEND de la classe ArticleRepository pour fonctionner correctement
            Ici Symfony comprend que la classe méthode blog() attend en argument un objet issu de la classe ArticleRepository, automatiquement Symfony envoi une instance de cette classe en argument
            $repoArticle est un objet issu de la classe ArticleRepository, nous n'avons plus qu'à piocher dans l'objet pour atteindre des méthodes de la classe

            Symfony est une application qui est capable de répondre à un navigateur lorsque celui-ci appel une adresse (ex: localhost:8000/blog), le controller doit être capable d'envoyer un rendu, un template sur le navigateur

            Ici, lorsque l'on transmet la route '/blog' dans l'URL, cela execute la méthode index() dans le controller qui renvoie le template '/blog/index.html.twig' sur le navigateur
        */

        // Pour selectionner des données en BDD, nous devons passer par une classe Repository, ces classes permettent uniquement d'executer des requêtes de selection SELECT en BDD. Elles contiennent des méthodes mis à disposition par Symfony (findAll(), find(), findBy() etc...)

        // Ici nous devons importer au sein de notre controller la classe Article Repository pour pouvoir selectionner dans la table Article
        // $repoArticle() est un objet issu de la classe ArticleRepository
        // getRepository() est une méthode issue de l'objet Doctrine permettant ici d'importer la classe ArticleRepository
        // $repoArticle = $doctrine->getRepository(Article::class);
        // dump(), dd() : outil de debug de Symfony
        // dump($repoArticle);

        // findAll() : méthode issue de la classe ArticleRepository permettant de selectionner l'ensemble de la table SQL et de récupérer un tableau multi contenant l'ensemble des articles stocké en BDD
        $articles = $repoArticle->findAll(); // SELECT * FROM article + fetchAll()
        // dd($articles);

        return $this->render('blog/blog.html.twig', [
            'articles' => $articles // on transmet au template les articles selectionnés en BDD afin que twig traite l'affichage
        ]);
    }

    // Méthode permettant d'insérer / modifier un article en BDD
    #[Route('/blog/new', name: 'blog_create')]
    #[Route('/blog/{id}/edit', name: 'blog_edit')]
    public function blogCreate(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        // La classe request de Symfony contient toute les données véhiculées par les super globales ($_GET, $_POST, $_SERVER, $_COOKIE etc...)
        // $request->request : la propriété 'request' de l'objet $request contient toute les données de $_POST

        /*

        // Si les données dans le tableau ARRAY $_POST sont supérieur à 0, alors on entre dans la condition IF
        if($request->request->count() > 0)
        {
            // Pour insérer dans la table SQL 'article', nous avons besoin d'un objet de son entité correspondante
            $article = new Article;

            $article->setTitre($request->request->get('titre'))
                    ->setContenu($request->request->get('contenu'))
                    ->setPhoto($request->request->get('photo'))
                    ->setDate(new \DateTime());

            // dd($article);

            // persist() : méthode issue de l'interface EntityManagerInterface permettant de préparer la requete d'insertion et de garder en mémoir l'objet / la requête
            $manager->persist($article);

            // flush() : méthode issue de l'interface EntityManagerInterface permettant véritablement d'executer la requete INSERT en BDD (ORM Doctrine)
            $manager->flush();
            
        } 

        */

        // Si la condition IF retourne TRUE, cela veut dire que $article contient un article stocké en BDD, on stock la photo actuelle de l'article dans la variable $photoActuelle
        if($article)
        {
            $photoActuelle = $article->getPhoto();
        }

        // Si la variable article est null, cela veut dire que nous sommes sur la route '/blog/new', on entre dans le IF et on crée une nouvelle instance de l'entité Article
        // Si la variable $article n'est pas null, cela veut dire que nous sommes sur la route '/blog/{id}/edit', nous n'entrons pas dans le IF car $article contient un article de la BDD
        if(!$article)
        {
            $article = new Article;
        }

        // Permet d'attribuer des values
        // $article->setTitre("Anthony est un chouineur")
        //         ->setContenu("Et en plus il est amoureux de Mickael");
        // handleRequest() permet d'envoyer chaque données de $_POST et de les transmettre aux bon setter de l'objet entité $article

        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {
            // le seul setter que l'on appel de l'entité, c'est celui de la date puisqu'il n'y a pas de champs 'date' dans le formulaire

            // Si l'article ne possède pas d'id, c'est une insertion, alors on entre dans la condition IF et on génère une date d'article
            if(!$article->getId())
                $article->setDate(new \DateTime());

            // DEBUT TRAITEMENT PHOTO

            $photo = $formArticle->get('photo')->getData();

            if($photo) // si une photo est uploadé dans le formulaire, on entre dans le IF et on traite l'image
            {
                $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

                $secureNomPhoto =  $slugger->slug($nomOriginePhoto);

                $nvNomFichier = $secureNomPhoto . '-' . uniqid() . '.' . $photo->guessExtension();

                // dd($nvNomFichier);

                try // on tente ici de copier l'image dans le dossier
                {
                    // on indique le chemin du dossier dans lequel la photo doit se copier
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $nvNomFichier
                    );
                }
                catch(FileException $e)
                {

                }

                // on insère le nom de l'image dans la BDD
                $article->setPhoto($nvNomFichier);
            }
            else // sinon aucune image n'a été uploadé, on renvoi dans la bdd la photo actuelle de l'article
            {
                // Si la photo actuelle est définit en BDD, alors en cas de modification, si on ne change pas de photo, on renvoi la photo actuelle en BDD
                if(isset($photoActuelle))
                    $article->setPhoto($photoActuelle);
                else
                    // Sinon aucune photo n'a été uploadé, on envoi la valeur null en BDD pour la photo
                    $article->setPhoto(null);
            }

            // FIN TRAITEMENT PHOTO

            // dd($article);

            // message validation en session
            if(!$article->getId())
                $txt = "ajouté";
            else
                $txt = "modifié";

            // méthode permettant d'enregistrer des messages utilisateurs accessibles en session
            $this->addFlash('success', "L'article a été $txt avec succès");

            $manager->persist($article);
            $manager->flush();

            // Une fois l'insertion/modification executée en BDD, on redirige l'internaute vers le détail de l'article, on transmet l'id à fournir dans l'URL en 2ème paramètre de la méthode redirectToRoute()
            return $this->redirectToRoute('blog_show', [
                'id' => $article->getId()
            ]);
        }   

        return $this->render('blog/blog_create.html.twig', [
            'form_article' => $formArticle->createView(), // On transmet le formulaire au template afin de pouvoir l'afficher avec Twig
            // createView() retourne un petit objet qui représente l'affichage du formulaire, on le récupère dans le template blog_create.html.twig
            'editMode' => $article->getId(),
            'photoActuelle' => $article->getPhoto()
        ]);
    } 

    // Méthode permettant d'afficher le détail d'un article
    // On définit une route 'paramétrée' {id}, ici la route permet de recevoir l'id d'un article stocké en BDD
    #[Route('/blog/{id}', name: 'blog_show')]
    public function blogShow(Article $article, Request $request, EntityManagerInterface $manager): Response
    {
        /*
            Ici, nous envoyons un ID dans l'url et nous imposons en argument un objet issu de l'entité Article donc la table SQL
            Donc Symfony est capable de selectionner en BDD l'article en fonction de l'id passé dans l'URL et de l'envoyer automatiquement en argument de la méthode blogShow() dans la variable de reception $article
        */

        // $repoArticle = $doctrine->getRepository(Article::class);

        // $articles = $repoArticle->find($id);

        // l'id dans la route '/blog/12' est transmit automatiquement en argument de la méthode BlogShow($id) dans la variable réception $id
        // dd($article); // 12

        // cette méthode mise à disposition retourne un objet App\Entity\Article contenant toute les données de l'utilisateur authentifié sur le site
        $user = $this->getUser();
        // dd($user);

        $comments = new Commentaire;

        $formComments = $this->createForm(CommentsType::class, $comments, [
            'commentFormFront' => true
        ]);

        $formComments->handleRequest($request);

        if($formComments->isSubmitted() && $formComments->isValid())
        {
            $user = $this->getUser();

            $comments->setDate(new \DateTime());

            $comments->setArticle($article);

            $comments->setAuteur($user->getPrenom() . ' ' . $user->getNom());

            $manager->persist($comments);
            $manager->flush();

            $this->addFlash('success', "Félicitations ! Votre commentaire a bien été posté");

            return $this->redirectToRoute('blog_show', [
                'id' => $article->getId()
            ]);
        }

        return $this->render('blog/blog_show.html.twig', [
            'articles' => $article,
            'formComments' => $formComments->createView(),
            'user' => $user
        ]);
    }
}