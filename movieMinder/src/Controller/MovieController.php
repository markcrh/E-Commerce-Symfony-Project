<?php

namespace App\Controller;


use App\Entity\Genre;
use App\Entity\Movie;
use App\Form\MovieType;
use App\Repository\MovieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\MovieSearchType;

#[Route('/movie')]
class MovieController extends AbstractController
{
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
            $movieGenres = $movie->getGenres();
            foreach ($movieGenres as $genre) {
                $movie->genresName[] = $genre->getName();
            }
        }

        return $this->render('movie/index.html.twig', [
            'movies' => $entityManager->getRepository(Movie::class)->findAll(),
            'user' => $user,
            'movieList' => $movieList,
            'watchedMovies' => $watchedMovies
        ]);
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

    #[Route('/card', name: 'app_movie_card')]
    public function showCard(Request $request): Response
    {
        return $this->render('components/card.html.twig', [
        ]);
    }


    #[Route('/{id}', name: 'app_movie_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $movie = $em->getRepository(Movie::class)->find($id);

        if (!$movie) {
            throw $this->createNotFoundException('The movie does not exist');
        }

        $user = $this->getUser();

        $userRating = null;
        if ($user) {
            $query = $em->createQuery(
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
        if ($this->isCsrfTokenValid('delete'.$movie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($movie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_movie_index', [], Response::HTTP_SEE_OTHER);
    }


    //Ratings!!!!!!


    #[Route('/{id}/rate', name: 'app_movie_rate', methods: ['POST'])]
    public function rateMovie(int $id, Request $request, EntityManagerInterface $em)
    {

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }


        $movie = $em->getRepository(Movie::class)->find($id);
        if (!$movie) {
            $this->addFlash('error', 'The movie does not exist.');
            return $this->redirectToRoute('app_movie_index'); // O a donde desees redirigir
        }


        if (!in_array($movie->getId(), $user->getMoviesId())) {
            $this->addFlash('error', 'You must watch the movie before rating it.');
            return $this->redirectToRoute('app_movie_show', ['id' => $movie->getId()]);
        }


        $rating = (int) $request->request->get('rating');
        if ($rating < 1 || $rating > 10) {
            $this->addFlash('error', 'Rating must be between 1 and 10.');
            return $this->redirectToRoute('app_movie_show', ['id' => $movie->getId()]);
        }


        $conn = $em->getConnection();
        $sql = 'INSERT INTO user_movie (user_id, movie_id, rating) 
            VALUES (:user_id, :movie_id, :rating) 
            ON DUPLICATE KEY UPDATE rating = :rating';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'user_id' => $user->getId(),
            'movie_id' => $movie->getId(),
            'rating' => $rating,
        ]);


        $this->updateMovieRating($movie, $em);


        $this->addFlash('success', 'Your rating has been saved!');
        return $this->redirectToRoute('app_movie_show', ['id' => $movie->getId()]);
    }


    private function updateMovieRating(Movie $movie, EntityManagerInterface $em)
    {

        $query = $em->createQuery(
            'SELECT AVG(um.rating) 
        FROM App\Entity\UserMovie um 
        WHERE um.movie = :movie'
        )
            ->setParameter('movie', $movie);


        $averageRating = $query->getSingleScalarResult();
        $averageRating = $averageRating !== null ? round($averageRating) : null;

        $movie->setRating($averageRating);
        $em->flush();
    }



}
