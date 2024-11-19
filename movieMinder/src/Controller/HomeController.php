<?php

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController extends AbstractController
{
    // Show the homepage with the search form
    #[Route('/', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $movies = $entityManager->getRepository(Movie::class)->findBy([], null, 6);
        return $this->render('home/index.html.twig', [
            'movies' => $movies,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('contact/contact.html.twig', [

        ]);
    }


    #[Route('/search', name: 'app_movie_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        $movieList = $user->getMoviesId();
        $movies = $entityManager->getRepository(Movie::class)->findBy(['id' => $myMovies], null,);
        $watched = $user->getWatchedMovies();
        $watchedMovies = $watched->map( function (Movie $movie) {
            return $movie->getId();
        })->toArray();

        $movies = [];

            $searchTerm = $request->get('search');
            if (is_string($searchTerm)) {
                $movies = $entityManager->getRepository(Movie::class)
                    ->createQueryBuilder('m')
                    ->where('m.title LIKE :title')
                    ->orWhere('m.year = :year')
                    ->setParameter('title', '%' . $searchTerm . '%')
                    ->setParameter('year', $searchTerm)
                    ->getQuery()
                    ->getResult();
            }

        return $this->render('search/results.html.twig', [
            'movies' => $movies,
            'searchTerm' => $searchTerm,
            'watchedMovies' => $watchedMovies,
            'movieList' => $movieList,
        ]);
    }

}
