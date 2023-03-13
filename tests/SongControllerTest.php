<?php

namespace App\Tests;


use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Controller\SongController;
use App\Entity\Rate;
use App\Entity\Song;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Form\FormInterface;

class SongControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $songRepository = $this->createMock(SongRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $songController = new SongController($songRepository, $em);
        // Create a song with rates
        $song = new Song();
        $song->setBand('This is a test song.');
        $song->setSongName('Test Song');
        $song->addRate(new Rate(3));
        $em->persist($song);
        $em->flush();

        // Call the index action
        $controller = new SongController($songRepository, $em);
        $response = $controller->index();
        // Assert that the response is successful
        $this->assertEquals(200, $response->getStatusCode());
        // Assert that the song rates were calculated correctly
        $song = $songRepository->findOneBy(['song_name' => 'Test Song']);
        $this->assertEquals(7, $song->sum);
        $this->assertEquals(3.5, $song->avr);
    }

    public function testSongVote(): void
    {
        // Arrange
        $song = new Song();
        $song->setBand('FnM');
        $request = new Request([], [], ['vote' => 4]);

        $rate = new Rate();
        $rate->setPoints(4);
        $rate->setUserId(1);
        $rate->setSong($song);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($rate);
        $em->expects($this->once())
            ->method('flush');

        $songRepository = $this->createMock(SongRepository::class);
        $controller = new SongController($songRepository, $em);

        $response = $controller->songVote($song, $request);

        $this->assertEquals('songs', $response->getTargetUrl());

        $this->assertEquals(
            'User test@example.com with id (1) voted (4) points successfully!',
            $controller->get('session')->getFlashBag()->get('success')[0]
        );
    }

    public function testCreate()
    {
        // Mock the necessary dependencies
        $songRepository = $this->getMockBuilder(SongRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new SongController($songRepository, $em);

        // Create a mock request object
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('isMethod')->willReturn(true);
        $request->method('getMethod')->willReturn('POST');
        $request->method('get')->willReturnMap([
            ['song_form', null, []],
            ['song_form[band]', null, 'fnm'],
            ['song_form[song_name]', null, 'epic'],
        ]);
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->method('handleRequest')->with($request)->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(new Song());
        $form->method('get')->willReturnMap([
            ['image', null, $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock()],
        ]);
        $form->expects($this->once())->method('createView')->willReturn([]);

        // Call the create() method
        $response = $controller->create($request);

        // Assert that the response is a redirect
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }
}
