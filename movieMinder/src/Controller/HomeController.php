<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    // Show the homepage with the search form
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        return $this->render('home/index.html.twig', [
        ]);
    }
    #[Route('/search', name: 'app_movie_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em): Response
    {

        $movies = [];

            $searchTerm = $request->get('search');
            if (is_string($searchTerm)) {
                $movies = $em->getRepository(Movie::class)
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
        ]);
    }
}
