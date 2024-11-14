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
        $form = $this->createForm(MovieSearchType::class);
        $form->handleRequest($request);

        // Pass the form to the template
        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Handle the search request and display results
    #[Route('/search/', name: 'app_movie_search', methods: ['GET', 'POST'])]
    public function search(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MovieSearchType::class);
        $form->handleRequest($request);

        $movies = [];
        $searchTerm = '';

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('title')->getData();

            // Only perform the search if the search term exists
            if (is_string($searchTerm)) {
                $movies = $em->getRepository(Movie::class)
                    ->createQueryBuilder('m')
                    ->where('m.title LIKE :title')  // Search by title
                    ->orWhere('m.year = :year')    // Search by year (exact match)
                    ->setParameter('title', '%' . $searchTerm . '%')  // Wildcards for title search
                    ->setParameter('year', $searchTerm)
                    ->getQuery()
                    ->getResult();
//            } else if (is_numeric($searchTerm)){
//                $movies = $em->getRepository(Movie::class)
//                    ->createQueryBuilder('m')
//                    ->where('m.year LIKE :year')
//                    ->setParameter('year', '%' . $searchTerm . '%')
//                    ->getQuery()
//                    ->getResult();
//            }z
            }
        }

        // Pass the form and the movie results to the template
        return $this->render('search/results.html.twig', [
            'form' => $form->createView(),
            'movies' => $movies,  // Pass the search results
            'searchTerm' => $searchTerm,  // Optionally pass the search term for display
        ]);
    }
}
