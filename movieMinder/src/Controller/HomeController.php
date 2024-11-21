<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\UserMovie;
use App\Service\MovieRatingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController extends AbstractController
{
    private $movieRatingService;
    function __construct(MovieRatingService $movieRatingService){
        $this->movieRatingService = $movieRatingService;
    }

    // Show the homepage with the search form
    #[Route('/', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $url = $request->headers->get('referer');
        $user = $this->getUser();
        $movies = $entityManager->getRepository(Movie::class)->findBy([], null, 6);
        foreach ($movies as $movie) {
            $movies[] = $movie;
        }
        $userMovie = $entityManager->getRepository(UserMovie::class)->findOneBy(['movie' => $movie, 'user' => $user]);
        $allUserMovies = $entityManager->getRepository(UserMovie::class)->findAll();
        $userRating = null;
        if ($user) {
            $query = $entityManager->createQuery(
                'SELECT um.rating FROM App\Entity\UserMovie um 
            WHERE um.user = :user AND um.movie = :movie'
            )
                ->setParameter('user', $user)
                ->setParameter('movie', $movie);

            $userRating = $query->getOneOrNullResult();
        }

        return $this->render('home/index.html.twig', [
            'movies' => $entityManager->getRepository(Movie::class)->findBy([], null, 6),
            'userRating' => $userRating ? $userRating['rating'] : null,
            'userMovie' => $userMovie,
            'allUserMovies' => $allUserMovies,
            'url' => $url,

        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('contact/contact.html.twig', [

        ]);
    }


    #[Route('/about', name: 'app_about')]
    public function about(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('about/about.html.twig', [

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

        $allUserMovies = $entityManager->getRepository(UserMovie::class)->findBy(['user' => $user]);
        $movies = [];
        $movie = $entityManager->getRepository(Movie::class)->findOneBy(['id' => $myMovies]);
        $userMovie = $entityManager->getRepository(UserMovie::class)->findOneBy(['movie' => $movie, 'user' => $user]);

        $userRating = null;
        if (!$userMovie == null){
            $userRating = $userMovie->getRating();
            $rating = (int) $request->request->get('rating');
            $this->movieRatingService->rateMovie($movie, $user, $rating);
            $entityManager->flush();
        }

            $searchTerm = $request->get('search');
            if (is_string($searchTerm)) {
                $movies = $entityManager->getRepository(Movie::class)
                    ->createQueryBuilder('movie')
                    ->where('movie.title LIKE :title')
                    ->setParameter('title', $searchTerm . '%')
                    ->getQuery()
                    ->getResult();
            }

        return $this->render('search/results.html.twig', [
            'movies' => $movies,
            'searchTerm' => $searchTerm,
            'watchedMovies' => $watchedMovies,
            'movieList' => $movieList,
            'userRating' => $userRating,
            'userMovie' => $userMovie,
            'allUserMovies' => $allUserMovies,

        ]);
    }

}
