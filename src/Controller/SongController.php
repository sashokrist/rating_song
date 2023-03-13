<?php

namespace App\Controller;

use App\Entity\Rate;
use App\Entity\Song;
use App\Form\SongFormType;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{
    private $songRepository;
    private $em;


    public function __construct(SongRepository $songRepository, EntityManagerInterface $em)
    {
        $this->songRepository = $songRepository;
        $this->em = $em;
    }

    #[Route('/', name: 'songs')]
    public function index(): Response
    {
        $songs = $this->songRepository->findAll();
        foreach ($songs as $song) {
            $rates = $song->getRates();
            $sum = array_reduce($rates->toArray(), fn($acc, $rate) => $acc + $rate->getPoints(), 0);
            $average = count($rates) ? $sum / count($rates) : 0;
            $song->sum = $sum;
            $song->avr = $average;
        }
        return $this->render('song/index.html.twig', [
            'songs' => $songs,
        ]);
    }

    #[Route('/song/{id}/vote', name: 'app_song_vote', methods: ['POST'])]
    public function songVote(Song $song, Request $request): Response
    {
        $vote = new Rate();
        $vote->setPoints($request->request->getInt('vote'));

        if ($this->getUser()) {
            $vote->setUserId($this->getUser()->getId());
        }

        $vote->setSong($song);

        $this->em->persist($vote);
        $this->em->flush();

        $this->addFlash(
            'success',
            sprintf(
                'User %s with id (%d) voted (%d) points successfully!',
                $this->getUser()->getEmail(),
                $this->getUser()->getId(),
                $request->request->getInt('vote')
            )
        );

        return $this->redirectToRoute('songs');
    }


    #[Route('/songs/create', name: 'create_song')]
    public function create(Request $request): Response
    {
        $song = new Song();
        $form = $this->createForm(SongFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newSong = $form->getData();

            $imagePath = $form->get('image')->getData();
            if ($imagePath) {
                $newFileName = uniqid('', true) . '.' . $imagePath->guessExtension();

                try {
                    $imagePath->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $newSong->setImage('/uploads/' . $newFileName);
            }
            $this->em->persist($newSong);
            $this->em->flush();

            $this->addFlash('success', 'Yor created new song successfully!');

            return $this->redirectToRoute('songs');
        }

        return $this->render('song/create.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
