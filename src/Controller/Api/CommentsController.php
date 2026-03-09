<?php

namespace App\Controller\Api;

use App\Entity\Comments;
use App\Entity\Posts;
use App\Entity\User;
use App\Repository\CommentsRepository;
use App\Repository\PostsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;

#[Route('/api/comments')]
class CommentsController extends ApiController
{
    private $entityManager;
    private $passwordHasher;
    private $jwtManager;
    private $tokenStorage;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
        TokenStorageInterface $tokenStorage,
    )
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('', name: 'add_comments', methods: ['POST'])]
    public function add(Request $request, MailerInterface $mailer, PostsRepository $postsRepository): JsonResponse
    {


        $content = $request->getContent();

        $cookie = $request->cookies->get('jwt');
        $token = new JWTUserToken();
        $token->setRawToken($cookie);
        $tokenData = $this->jwtManager->decode($token);

        $user = $tokenData['username'];
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user]);

        if (!$cookie && !$user) {
            return new JsonResponse(['message' => 'Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer ultérieurement.'], 400);
        }







            $data = json_decode($content, true);

            $post = $postsRepository->findOneBy(['id' => $data['posts']]);

            $comment = new Comments();
            $comment->setUser($data['user']);
            $comment->setEmail($data['email']);
            $comment->setComment($data['comment']);
            $comment->setAccepted(false);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $post->addComment($comment);

            $this->entityManager->persist($post);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $email = (new TemplatedEmail())
            ->to($_ENV['MAILER_TO_WEBMASTER'])
            ->from($_ENV['MAILER_TO'])
            ->subject('Nouveau Commentaire de ' . $data['user'] )
            ->htmlTemplate('emails/comments.html.twig')
            ->context([
                'user' => $data['user'],
                'emailUser' => $data['email'],
                'comment' => $data['comment'],
                'posts' => $post->getTitle(),
                'id' => $comment->getId(),
            ])
            ->replyTo($data['email']);

        $mailer->send($email);

        $cookie = new Cookie('jwt', '', time() - 3600, '/', 'localhost', false, false, 'lax');

        $response = new JsonResponse(['message' => 'Votre commentaire a bien été envoyé ! On le valide au plus vite !'], 200);

        $response->headers->setCookie($cookie);

        return $response;

        if (empty($data['user']) || empty($data['email']) || empty($data['comment']) || empty($data['posts'])  ) {
            return $this->json(
                [
                    "erreur" => "Erreur de saisie",
                    "code_error" => 400
                ],
                Response::HTTP_NOT_FOUND, // 400
            );
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(
                [
                    "erreur" => "Adresse e-mail invalide",
                    "code_error" => 400
                ],
                Response::HTTP_BAD_REQUEST, // 400
            );
        }

        return new JsonResponse(['message' => 'Votre commentaire a bien été envoyé ! On le valide au plus vite !'], 200);


        }

    #[Route('/delete/{id}', name: 'admin_comment_delete', methods: ["GET", "POST"])]
    public function delete(Request $request,  CommentsRepository $CommentsRepository): Response
    {

        if ($request->isMethod('GET')) {
            $comment = $CommentsRepository->find($request->get('id'));
            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            return new RedirectResponse('https://unetaupechezvous.fr/');

        }
    }

    #[Route('/validate/{id}', name: 'admin_comment_validate', methods: ["GET", "POST"])]
    public function validate(Request $request, CommentsRepository $CommentsRepository ): JsonResponse
    {

        if ($request->isMethod('GET')) {

            $comment = $CommentsRepository->find($request->get('id'));
            $comment->setAccepted(true);
            $comment->isReplyToComment(true);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();


            return $this->json(
                [
                    "accepted" => $comment->isAccepted(),
                    "message" => "Commentaire validé",

                ],
                Response::HTTP_OK,
            );

        }

    }

    #[Route('/verify_email', name: 'verify', methods: ['POST'])]
    public function verifyEmail(Request $request, HttpClientInterface $httpClient, EntityManagerInterface $entityManager): JsonResponse
    {
    $email = JSON_decode($request->getContent(), true)['email'];

    try {
        $urlAPI = 'https://api.mailcheck.ai/email/' . $email;

        $reponse = $httpClient->request('GET', $urlAPI);
        $donnees = $reponse->toArray();

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {

            $token = $this->jwtManager->create($existingUser);
            $date = time() + (3600 * 24 * 365);

            $cookie = new Cookie(
                'jwt',
                $token,
                $date,

                // '/',        // Le chemin du cookie (par exemple, '/')
                // '',        // Le domaine du cookie (null pour le domaine actuel)
                // true,  // Désactivez l'option Secure pour permettre les connexions HTTP
                // false,  // Désactivez l'option HttpOnly pour permettre l'accès via JavaScript
                // 'lax',
            );

            $response = new JsonResponse(['message' => true]);
            $response->headers->set('Content-Type', 'application/json');

            $response->headers->setCookie($cookie);

            return $response;

        }

        if ($donnees['disposable']) {
            return new JsonResponse(['message' => 'L\'e-mail est jetable et n\'est pas accepté.'], 400);
        }

        if (!$donnees['mx'] ) {
            return new JsonResponse(['message' => 'L\'e-mail est invalide.'], 400);
        }
        if (!$existingUser) {

            $currentDate = new \DateTimeImmutable();
            $password = $currentDate->format('Ymd');
            $password .= random_int(1000, 9999);

            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_COMMENT']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password ));

            $entityManager->persist($user);
            $entityManager->flush();

            $token = $this->jwtManager->create($user);


            $cookie = new Cookie(
                'jwt',
                $token,

            );


            $response = new JsonResponse(['message' => true]);

            $response->headers->setCookie($cookie);

            return $response;

        }

    } catch (\Exception $e) {
        return new JsonResponse(['message' => 'Veuillez vérifier l\'adresse e-mail fournie.'], 400);
    }

    }
}
