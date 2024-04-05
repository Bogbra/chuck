<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Joke;

class JokeController extends AbstractController
{
    private $entityManager;
    private $jokeRepository;

    public function __construct(
        private HttpClientInterface $client,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->jokeRepository = $entityManager->getRepository(Joke::class);
    }  
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('joke/homepage.html.twig');
    }
    #[Route('/joke', name: 'app_joke')]
    public function joke(): Response
    {
        $randomJoke = null;

        try {
            $jokeText = $this->fetchChuckNorrisJoke();
                    
            $existingJoke = $this->jokeRepository->findOneBy(['joke' => $jokeText]);

            if (!$existingJoke) {
                $joke = new Joke();
                $joke->setJoke($jokeText);
    
                $this->entityManager->persist($joke);
                $this->entityManager->flush();
            } else {
                $joke = $existingJoke;
            }    
        } catch (\Exception $e) {
            $jokeCount = $this->jokeRepository->count([]);
            $randomIndex = random_int(0, $jokeCount - 1);
            $randomJoke = $this->jokeRepository->findOneBy(array('id' => $randomIndex));
            $jokeText = $randomJoke ? $randomJoke->getJoke() : 'Chuck Norris is too busy to tell a joke right now.';
        }

        return $this->render('joke/index.html.twig', [
            'joke' => $jokeText,
            'randomJoke' => $randomJoke
        ]);
    }

    private function fetchChuckNorrisJoke(): string
    {
        $response = $this->client->request(
            'GET',
            'https://api.chucknorris.io/jokes/random'
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Chuck Norris jokes API failed to respond.');
        }

        $content = $response->toArray();
        
        return $content['value'];
    }
}
