<?php

namespace App\Controller\Back;

use App\Entity\Comments;
use App\Form\CommentsType;
use App\Repository\CommentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/comments')]
class CommentsController extends AbstractController
{
    #[Route('/', name: 'app_back_comments_index', methods: ['GET'])]
    public function index(CommentsRepository $commentsRepository): Response
    {
        return $this->render('back/comments/index.html.twig', [
            'comments' => $commentsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_comments_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comments();
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_comments_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/comments/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_comments_show', methods: ['GET'])]
    public function show(Request $request, Comments $comment, EntityManagerInterface $entityManager): Response
    {

        // Créez une nouvelle instance de l'entité Comment pour représenter la réponse
        $reply = new Comments(); // Assurez-vous que Comment est le nom correct de votre entité

        // Créez le formulaire pour la réponse
        $form = $this->createForm(CommentsType::class, $reply); // Assurez-vous d'ajuster le nom du formulaire selon votre application

        // Gérez la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associez la réponse au commentaire parent
            $reply->setParent($comment);

            // Enregistrez la réponse dans la base de données
            $entityManager->persist($reply);
            $entityManager->flush();

            // Redirigez ou effectuez d'autres actions nécessaires
            return $this->redirectToRoute('app_back_comments_show', ['id' => $comment->getId()]);
        }
        return $this->render('back/comments/show.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(), // Passez le formulaire au modèle Twig
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_comments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comments $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_comments_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/comments/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_comments_delete', methods: ['POST'])]
    public function delete(Request $request, Comments $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_comments_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reply', name: 'app_back_comments_reply', methods: ['POST'])]
    public function replyToComment(Request $request, Comments $comment, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {

        $reply = new Comments();

        $form = $this->createForm(CommentsType::class, $reply);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setParent($comment);

            $reply->setCreatedAt(new \DateTimeImmutable());
            $reply->setAccepted(true);

            $articleComment = $comment->getPosts();

            $reply->setPosts($articleComment);

            $entityManager->persist($reply);
            $entityManager->flush();

            $userComment = $reply->getParent();

            $email = (new TemplatedEmail())
                ->to($userComment->getEmail())
                ->from($_ENV['MAILER_TO'])
                ->subject('Réponse à votre commentaire sur Une Taupe Chez Vous' )
                ->htmlTemplate('emails/reply_notification_email.html.twig')
                ->context([
                    'username' => $userComment->getUser(),
                    'articleTitle' => $articleComment->getTitle(),
                    'articleLink' => 'https://unetaupechezvous.fr' . $articleComment->getUrl(),
                    'replyContent' => $reply->getComment(),
                ]);

            $mailer->send($email);

            $emailReturn = (new TemplatedEmail())
                ->to($_ENV['MAILER_TO'])
                ->from($_ENV['MAILER_TO'])
                ->subject('Votre commentaire sur Une Taupe Chez Vous' )
                ->htmlTemplate('emails/reply_notification_email_To.html.twig')
                ->context([
                    'username' => $reply->getUser(),
                    'articleTitle' => $articleComment->getTitle(),
                    'articleLink' => 'https://unetaupechezvous.fr' . $articleComment->getUrl(),
                    'replyContent' => $reply->getComment(),
                ]);
            $mailer->send($emailReturn);

            return $this->redirectToRoute('app_back_comments_index');
        }


        return $this->redirectToRoute('app_back_comments_index');
    }
}
