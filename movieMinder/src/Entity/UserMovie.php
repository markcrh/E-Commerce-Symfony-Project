<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserMovieRepository; // Si tienes un repositorio personalizado
use App\Entity\User;
use App\Entity\Movie;

#[ORM\Entity(repositoryClass: UserMovieRepository::class)]
#[ORM\Table(name: "user_movie")]
class UserMovie
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "userMovies")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: "userMovies")]
    #[ORM\JoinColumn(name: "movie_id", referencedColumnName: "id", nullable: false)]
    private ?Movie $movie = null;

    #[ORM\Column(type: "integer")]
    private ?int $rating = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): self
    {
        $this->movie = $movie;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }
}
