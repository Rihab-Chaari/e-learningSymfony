<?php

namespace App\Controller;

use App\Entity\Courses;
use App\Entity\Enrollments;
use App\Entity\Users;
use App\Form\CoursesType;
use App\Repository\CoursesRepository;
use App\Repository\EnrollmentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/courses')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CoursesController extends AbstractController
{
    #[Route('/', name: 'app_courses_index', methods: ['GET'])]
    public function index(CoursesRepository $coursesRepository): Response
    {
        $currentUser = $this->getUser();
        return $this->render('courses/index.html.twig', [
            'courses' => $coursesRepository->findAll(),
            'user' => $currentUser,
            'title' => 'List of courses'
        ]);
    }

    #[Route('/new', name: 'app_courses_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $course = new Courses();
        $currentUser = $this->getUser();
        $form = $this->createForm(CoursesType::class, $course);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();
    
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
    
                try {
                    $image->move(
                        $this->getParameter('app.profile_images'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }
    
                $course->setImage($newFilename);
            }
    
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
    
                $course->setPdfFile($newPdfName);
            }
    
            $course->setInstructor($currentUser);
            $entityManager->persist($course);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_courses_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('courses/new.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
            'image_path' => $course->getImage(),
            'title' => 'Create a course'
        ]);
    }
    

    #[Route('/{id}', name: 'app_courses_show', methods: ['GET'])]
    public function show(Request $request, Courses $course): Response
    {
        return $this->render('courses/show.html.twig', [
            'course' => $course,
            'title' => 'Information about the course "'.$course->getTitle().'"'
        ]);
    }


    #[Route('/{id}/edit', name: 'app_courses_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function edit(Request $request, Courses $course, EntityManagerInterface $entityManager , SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CoursesType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('app.courses_images'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

                $course->setImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_courses_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('courses/edit.html.twig', [
            'course' => $course,
            'form' => $form,
            'image_path' => $course->getImage(),
            'title' => 'Edit course <br>' . $course->getId()
        ]);
    }

    #[Route('/{id}/enrollements', name: 'app_courses_show_enrollements', methods: ['GET'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function showEnrollements(Request $request, Courses $course): Response
    {
        return $this->render('courses/showEnrollements.html.twig', [
            'course' => $course,
            'enrollements' => $course->getEnrollments(),
            'title' => 'Enrollements for "' . $course->getTitle() . '"'
        ]);
    }

    #[Route('/{id}/enroll', name: 'app_courses_enroll', methods: ['GET'])]
    public function enroll(Request $request, Courses $course, EntityManagerInterface $entityManager): Response
    {
        $enrollment = new Enrollments();
        $enrollment->setCourse($course);
        $enrollment->setUser($this->getUser());
        $entityManager->persist($enrollment);
        $entityManager->flush();
        return $this->redirectToRoute('app_courses_show', ['id' => $request->get('id')]);
    }

    #[Route('/{id}/unenroll', name: 'app_courses_unenroll', methods: ['GET'])]
    public function unenroll(Courses $course, EntityManagerInterface $entityManager,
                             EnrollmentsRepository $enrollmentsRepository): Response
    {
        $enrollment = $enrollmentsRepository->findOneBy(['course' => $course, 'user' => $this->getUser()]);
        $entityManager->remove($enrollment);
        $entityManager->flush();
        return $this->redirectToRoute('app_courses_index');
    }

    #[Route('/{id}', name: 'app_courses_delete', methods: ['POST'])]
    #[IsGranted('ROLE_INSTRUCTOR')]
    public function delete(Request $request, Courses $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_courses_index', [], Response::HTTP_SEE_OTHER);
    }
}