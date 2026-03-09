<?php

namespace App\Controller\Api;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('', name: '', methods: ['POST'])]
    public function add(Request $request, MailerInterface $mailer): JsonResponse
    {

    $content = $request->getContent();
    $data = $request->request->all();
    try {
        $uploadedFile = $request->files->get('image');

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
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
        if (!$uploadedFile) {
            return $this->json([
                "erreur" => "Aucun fichier n'a été téléchargé",
                "code_error" => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($uploadedFile ) {
            $uploadDir = $this->getParameter('app.imgDir');
            $filePath = $uploadDir . '/' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();

            $uploadedFile->move($uploadDir, basename($filePath));

            $img = $this->imagine->open($filePath);

            $img->resize(new Box(750, 750));

            $img->save($filePath, ['webp_quality' => 80]);

            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);

            unlink($filePath);

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
                            'text' => "Veuillez identifier cette image en tant que " . $data['type'] ." Répondez simplement et naturellement, en utilisant un langage clair et courtois. Si vous ne reconnaissez pas l'image, veuillez l'indiquer poliment et suggérer de réessayer avec une autre image."
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

        sleep(5);

        return $this->json([
            'message' => $content
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
