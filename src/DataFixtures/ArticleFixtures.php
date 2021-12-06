<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // PHP namespace resolver : extension permettant d'importer les classes (ctrl + alt + i)
        // La boucle tourne 10 fois pour créer 10 articles fictifs dans la bdd
        for($i = 1; $i <=10; $i++)
        {
            // Pour insérer des données dans la table SQL Article, nous sommes obligé de passer par sa classe Entity correspondaite 'App/Entity/Article', cette classe est le reflet de la table SQL, elle contient des propriétés identiques aux champs/colonne de la table SQL
            $article = new Article;

            // On execute tous les setteurs de l'objet afin de remplir les objets et d'ajouter un titre, contenu, image etc... pour nos 10 articles
            $article->setTitre("Titre de l'article $i")
                    ->setContenu("<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Doloribus dolor nihil iste commodi ullam deleniti minima optio et voluptates ipsa.</p>")
                    ->setPhoto("https://picsum.photos/300/650")
                    ->setDate(new \DateTime());

            // Nous faisons appel à l'objet $manager ObjectManager
            // Une classe permet entre autre de manipuler les lignes de la BDD (INSERT, UPDATE, DELETE)
            // persist() : méthode issu de la classe ObjectManager (ORM Doctrine) permettant de garder en mémoire les 10 objets $articles et de préparer les requetes SQL
            $manager->persist($article);
        }

        // flush() : méthode issu de la classe ObjectManager (ORM Doctrine) permettant véritablement d'executer les requetes SQL en BDD
        $manager->flush();
    }
}