<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\User;
use App\Entity\UserMovie;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\MovieRatingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MovieRepository;


#[Route('/user')]
class UserController extends AbstractController
{
    private $movieRatingService;
    function __construct(MovieRatingService $movieRatingService){
        $this->movieRatingService = $movieRatingService;
    }
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function userDashboard(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        $movies = $entityManager->getRepository(Movie::class)->findBy(['id' => $myMovies], null, 8);
        $movie = $entityManager->getRepository(Movie::class)->findOneBy(['id' => $myMovies]);
        $watched = $user->getWatchedMovies();
        $watchedMovies = $watched->map( function (Movie $movie) {
            return $movie->getId();
        })->toArray();

        $userMovie = $entityManager->getRepository(UserMovie::class)->findOneBy(['movie' => $movie, 'user' => $user]);
        $userRating = $userMovie->getRating();

        $rating = (int) $request->request->get('rating');
        $this->movieRatingService->rateMovie($movie, $user, $rating);
        $entityManager->flush();
        
        return $this->render('dashboard/dashboard.html.twig', [
            'movies' => $movies,
            'watchedMovies' => $watchedMovies,
            'myMovies' => $myMovies,
            'userRating' => $userRating,
        ]);
    }


    #[Route('/mymovies', name:'show_list', methods: ['GET'])]
    public function getList(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        $movies = $entityManager->getRepository(Movie::class)->findBy(['id' => $myMovies], null,);
        $watched = $user->getWatchedMovies();
        $watchedMovies = $watched->map( function (Movie $movie) {
            return $movie->getId();
        })->toArray();

        $movie = $entityManager->getRepository(Movie::class)->findOneBy(['id' => $myMovies]);
        $userMovie = $entityManager->getRepository(UserMovie::class)->findOneBy(['movie' => $movie, 'user' => $user]);
        $userRating = $userMovie->getRating();

        $rating = (int) $request->request->get('rating');
        $this->movieRatingService->rateMovie($movie, $user, $rating);
        $entityManager->flush();

        return $this->render('user/user_movie_list.html.twig', [
            'movies' => $movies,
            'watchedMovies' => $watchedMovies,
            'userRating' => $userRating,

        ]);
    }
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    #[Route('/profile', name: 'app_user_profile', methods: ['GET'])]
    public function profile(UserRepository $userRepository, MovieRepository $movieRepository): Response
    {
        $user = $this->getUser();
        $myMovies = $user->getMoviesId();
        $movies = $movieRepository->findBy(['id' => $myMovies]);
        $watched = $user->getWatchedMovies();
        $watchedMovies = $watched->map( function (Movie $movie) {
            return $movie->getId();
        })->toArray();
        $totalDurationMovies = 0;
        foreach ($watched as $movie) {
            $totalDurationMovies += $movie->getDuration();
        }
        $watchedMovieCount = count($watched);

        return $this->render('profile/profile.html.twig', [
            'users' => $userRepository->findAll(),
            'movies' => $movies,
            'watchedMovies' => $watchedMovies,
             'totalDurationMovies' => $totalDurationMovies,
            'watchedMovieCount' => $watchedMovieCount
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/watched/{movieId}', name: 'app_user_add_watched', methods: ['GET','POST'])]
    public function addWatchedMovie(Request $request, EntityManagerInterface $entityManager, $movieId): Response
    {
        $movie = $entityManager->getRepository(Movie::class)->find($movieId);
        $user = $this->getUser();
        $url = $request->headers->get('referer');
        $user->addWatchedMovie($movie);
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirect($url, Response::HTTP_SEE_OTHER);
    }

}

