<?php

namespace App\Controller\Api;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\NamedAddress;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/pest-identification')]
class PestIdentificationController extends ApiController
{    
    private $tokenService;
    private $serializer;
    private $imagine;

    public function __construct(
        TokenStorageInterface $token,
        SerializerInterface $serializer,
    ) {
        $this->tokenService = $token;
        $this->serializer = $serializer;
        $this->imagine = new Imagine();

    }
	
    #[Route('', name: 'add_contact', methods: ['POST'])]
    public function add(Request $request, MailerInterface $mailer): JsonResponse
    {

    $content = $request->getContent();
    $data = $request->request->all();
    
    try {
    $uploadedFile = $request->files->get('image');

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $fileExtension = $uploadedFile->getClientOriginalExtension();

    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        return $this->json(
            [
                "erreur" => "Formats acceptés : " . implode(', ', $allowedExtensions),
                "code_error" => Response::HTTP_FORBIDDEN
            ],
            Response::HTTP_FORBIDDEN
        );
    }

    if ($uploadedFile ) {
        $img = $this->imagine->open($uploadedFile);
        $img->resize(new Box(750, 750));

        $img->save('img.webp', ['webp_quality' => 80]); // Sauvegarde l'image dans le flux

        $base64Image = base64_encode($img);
    } else {
        return $this->json(
            [
                "erreur" => "Erreur d'images, ressayer ulterieurement",
                "code_error" => Response::HTTP_FORBIDDEN
            ],
            Response::HTTP_FORBIDDEN
        );
     }

    if (strlen($data['type']) <= 2) {
        return $this->json(
            [
                "erreur" => "Erreur lors de la saisie du Type",
                "code_error" => Response::HTTP_FORBIDDEN
            ],
            Response::HTTP_FORBIDDEN
        );
    }

    $client = HttpClient::create();
    $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $_ENV['CHATGPT_API_KEY'],
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "C'est quoi l'image?"
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:image/jpeg;base64,{$base64Image}"
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 300
        ],
    ]);
    $data = $response->toArray();

    if (isset($data['choices']) && count($data['choices']) > 0) {

        $content = $data['choices'][0]['message']['content'];
        

        return $this->json([
            'content' => $content
        ]);
    }


    }
    catch (\Exception $e) {
        $email = (new TemplatedEmail())
        ->to($_ENV['MAILER_TO_WEBMASTER'])
        ->from($_ENV['MAILER_TO'])
        ->subject('Erreur lors de l\'envoie de l\'email')
        ->htmlTemplate('emails/error.html.twig')
        ->context([
            'error' => $e->getMessage(),
        ]);
        $mailer->send($email);


        return $this->json(
            [
                "erreur" => "Erreur lors de l'identification, veuillez réessayer plus tard",
                "code_error" => Response::HTTP_FORBIDDEN
            ],
            Response::HTTP_FORBIDDEN
        );
    }
}
            
}