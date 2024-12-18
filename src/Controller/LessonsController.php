<?php

namespace App\Controller;

use App\Entity\Lessons;
use App\Entity\Progress;
use App\Form\LessonsType;
use App\Repository\EnrollmentsRepository;
use App\Repository\LessonsRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/lessons')]
class LessonsController extends AbstractController
{
    #[Route('/', name: 'app_lessons_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(LessonsRepository $lessonsRepository): Response
    {
        return $this->render('lessons/index.html.twig', [
            'lessons' => $lessonsRepository->findAll(),
            'title' => 'List of lessons'
        ]);
    }

    #[Route('/new', name: 'app_lessons_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $lesson = new Lessons();
        $form = $this->createForm(LessonsType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ajout de la logique pour le fichier PDF
            /** @var UploadedFile $pdf */
            $pdf = $form->get('pdfFile')->getData();
    
            if ($pdf) {
                $originalPdfName = pathinfo($pdf->getClientOriginalName(), PATHINFO_FILENAME);
                $safePdfName = $slugger->slug($originalPdfName);
                $newPdfName = $safePdfName.'-'.uniqid().'.'.$pdf->guessExtension();
    
                try {
                    $pdf->move(
                        $this->getParameter('app.pdf_directory'),
                        $newPdfName
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }
    
                $lesson->setPdfFile($newPdfName);
            }
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lessons/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'title' => 'Create a lesson'
        ]);
    }

    #[Route('/{id}', name: 'app_lessons_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(Lessons $lesson): Response
    {
        return $this->render('lessons/show.html.twig', [
            'lesson' => $lesson,
            'title' => 'Information about lesson <br>"' . $lesson->getTitle() . '"'
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lessons_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function edit(Request $request, Lessons $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonsType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lessons/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'title' => 'Edit lesson <br>' . $lesson->getId()
        ]);
    }

    #[Route('/{id}', name: 'app_lessons_delete', methods: ['POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function delete(Request $request, Lessons $lesson, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_lessons_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_lessons_complete', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function complete(Lessons $lesson, EnrollmentsRepository $enrollmentsRepository, EntityManagerInterface $entityManager): Response
    {
        $enrollment = $enrollmentsRepository->findOneBy(['course' => $lesson->getCourse(), 'user' => $this->getUser()]);
        $progress = new Progress();
        $progress->setLessons($lesson);
        $progress->setStatus(1);
        $progress->setLastaccess(new \DateTimeImmutable());
        $progress->setEnrollment($enrollment);

        $entityManager->persist($progress);
        $entityManager->flush();

        return $this->redirectToRoute('app_courses_show', ['id' => $lesson->getCourse()->getId()]);
    }
}