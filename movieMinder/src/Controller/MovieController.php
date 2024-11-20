<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\User;
use App\Entity\UserMovie;
use App\Form\MovieType;
use App\Service\MovieRatingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\MovieSearchType;

#[Route('/movie')]
class MovieController extends AbstractController
{
    private $movieRatingService;
    function __construct(MovieRatingService $movieRatingService){
        $this->movieRatingService = $movieRatingService;
}
    #[Route('/', name: 'app_movie_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        $movieList = $user->getMoviesId();
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        $movies = $entityManager->getRepository(Movie::class)->findAll();
        $watched = $user->getWatchedMovies();
        $watchedMovies = $watched->map( function (Movie $movie) {
            return $movie->getId();
        })->toArray();
        foreach ($movies as $movie) {
            $movies[] = $movie;
        }
        $userRating = null;
        $userMovie = $entityManager->getRepository(UserMovie::class)->findOneBy(['user' => $user, 'movie' => $movieList]);

        return $this->render('movie/index.html.twig', [
            'movies' => $movies = $entityManager->getRepository(Movie::class)->findAll(),
            'user' => $user,
            'userRating' => $userRating ? $userRating['rating'] : null,
            'movieList' => $movieList
        ]);
    }

#[Route('/sortedAlp', name: 'app_movie_sortedAlp', methods: ['GET'])]
    public function sortedAlp(MovieRepository $movieRepository): Response
    {
        $user = $this->getUser();
        $movieList = $user->getMoviesId();
        $movies = $movieRepository->findAll();
        $sortedByTitle = $this->sortByTitle($movies);

        return $this->render('movie/indexSortedAlp.html.twig', [
            'moviesByTitle' => $sortedByTitle,
            'movieList' => $movieList,
            'movies' => $movies,
        ]);
    }

    #[Route('/sortedYear', name: 'app_movie_sortedYear', methods: ['GET'])]
    public function sortedYear(MovieRepository $movieRepository): Response
    {
        $user = $this->getUser();
        $movieList = $user->getMoviesId();
        $movies = $movieRepository->findAll();
        $sortedByYear = $this->sortByYear($movies);

        return $this->render('movie/indexSortedYear.html.twig', [
            'moviesByYear' => $sortedByYear,
            'movieList' => $movieList,
            'movies' => $movies,
        ]);
    }
    private function sortByYear($movies)
    {
        usort($movies, function($a, $b) {
            return $b->getYear() <=> $a->getYear();  // Use <=> for comparison
        });
        return $movies;
    }

    // Sort movies alphabetically by title
    private function sortByTitle($movies)
    {
        usort($movies, function($a, $b) {
            return strcmp($a->getTitle(), $b->getTitle());  // String comparison
        });
        return $movies;
    }


    #[Route('/add/{id}', name: 'add_movie_user', methods: ['GET', 'POST'])]
    public function addMovieUser(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $url = $request->headers->get('referer');
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        if (!in_array($id, $myMovies)) {
            $myMovies[] = $id;
            $user->setMoviesId($myMovies);
            $entityManager->flush();
        } else {
            unset($myMovies[array_search($id, $myMovies)]);
            $user->setMoviesId($myMovies);
            $entityManager->flush();
        }
        return $this->redirect($url);
    }

    #[Route('/new', name: 'app_movie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $movie = new Movie();
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($movie);
            $entityManager->flush();

            return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('movie/new.html.twig', [
            'movie' => $movie,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_movie_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $movie = $entityManager->getRepository(Movie::class)->find($id);

        if (!$movie) {
            throw $this->createNotFoundException('The movie does not exist');
        }

        $user = $this->getUser();

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
        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
            'userRating' => $userRating ? $userRating['rating'] : null,
        ]);

    }


    #[Route('/{id}/edit', name: 'app_movie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Movie $movie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('movie/edit.html.twig', [
            'movie' => $movie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_movie_delete', methods: ['POST'])]
    public function delete(Request $request, Movie $movie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $movie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($movie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
    }


    //Ratings!!!!!!


    #[Route('/{id}/rate', name: 'app_movie_rate', methods: ['POST'])]
    public function rateMovie(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        dd($request);
        $url = $request->headers->get('referer');
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $movie = $entityManager->getRepository(Movie::class)->find($id);
        if (!$movie) {
            $this->addFlash('error', 'The movie does not exist.');
            return $this->redirectToRoute('app_movie_index');
        }

        if (!in_array($movie->getId(), $user->getMoviesId())) {
            $this->addFlash('error', 'You must watch the movie before rating it.');
            return $this->redirectToRoute('app_movie_index', ['id' => $movie->getId()]);
        }

        $rating = (int) $request->request->get('rating');
        $this->movieRatingService->rateMovie($movie, $user, $rating);

        return $this->redirect($url);

    }

}