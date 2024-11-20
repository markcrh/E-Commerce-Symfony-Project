<?php
namespace App\Service;

use App\Entity\Movie;
use App\Entity\User;
use App\Entity\UserMovie;
use Doctrine\ORM\EntityManagerInterface;

class MovieRatingService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function rateMovie(Movie $movie, User $user, int $rating) : void
    {
        $userMovie = $this->entityManager->getRepository(UserMovie::class)->findOneBy(['movie' => $movie, 'user' => $user]);

        if (!$userMovie) {
            $userMovie = new UserMovie();
            $userMovie->setMovie($movie);
            $userMovie->setUser($user);
        }

        $userMovie->setRating($rating);
        $this->entityManager->persist($userMovie);

        $userMovies = $this->entityManager->getRepository(UserMovie::class)->findBy(['movie' => $movie]);
        $globalRating = 0;
        $totalMovies = 0;

        foreach ($userMovies as $userMovie) {
            $globalRating += $userMovie->getRating();
            $totalMovies++;
        }

        $averageRating = $totalMovies > 0 ? $globalRating / $totalMovies : 0;
        $movie->setRating($averageRating);
        $this->entityManager->flush();
    }
}
