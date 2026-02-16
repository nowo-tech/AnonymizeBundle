<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mongodb/customer-profile')]
class CustomerProfileController extends AbstractController
{
    private function getMongoClient()
    {
        if (!extension_loaded('mongodb')) {
            return null;
        }
        if (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client')) {
            return null;
        }
        $mongodbUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
        if (!$mongodbUrl) {
            return null;
        }
        try {
            $clientClass = class_exists('\MongoDB\Client') ? '\MongoDB\Client' : 'MongoDB\Client';
            return new $clientClass($mongodbUrl);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getCollection()
    {
        $client = $this->getMongoClient();
        if (!$client) {
            return null;
        }
        $mongodbUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
        $parsedUrl = parse_url(str_replace('mongodb://', 'http://', $mongodbUrl));
        $databaseName = trim($parsedUrl['path'] ?? 'anonymize_demo', '/');
        $databaseName = explode('?', $databaseName)[0];
        $database = $client->selectDatabase($databaseName);
        return $database->selectCollection('customer_profiles');
    }

    #[Route('/', name: 'mongodb_customer_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!extension_loaded('mongodb') || (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client'))) {
            $errorMsg = 'MongoDB PHP extension or MongoDB\Client class not found.';
            $this->addFlash('error', $errorMsg);
            return $this->render('customer_profile/index.html.twig', [
                'profiles' => [],
                'connection' => 'mongodb',
                'error' => $errorMsg,
            ]);
        }

        $collection = $this->getCollection();
        if (!$collection) {
            $errorMsg = 'MongoDB connection not available.';
            $this->addFlash('error', $errorMsg);
            return $this->render('customer_profile/index.html.twig', [
                'profiles' => [],
                'connection' => 'mongodb',
                'error' => $errorMsg,
            ]);
        }

        try {
            $profiles = $collection->find([], ['sort' => ['createdAt' => -1]]);
            $profilesArray = [];
            foreach ($profiles as $profile) {
                $profilesArray[] = $profile;
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading profiles: ' . $e->getMessage());
            $profilesArray = [];
        }

        return $this->render('customer_profile/index.html.twig', [
            'profiles' => $profilesArray,
            'connection' => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/new', name: 'mongodb_customer_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $collection = $this->getCollection();
            if (!$collection) {
                $this->addFlash('error', 'MongoDB connection not available.');
                return $this->redirectToRoute('mongodb_customer_profile_index');
            }

            try {
                $data = [
                    'email' => $request->request->get('email', ''),
                    'firstName' => $request->request->get('firstName', ''),
                    'lastName' => $request->request->get('lastName', ''),
                    'phone' => $request->request->get('phone', ''),
                    'address' => $request->request->get('address', ''),
                    'company' => $request->request->get('company', ''),
                    'username' => $request->request->get('username', ''),
                    'website' => $request->request->get('website', ''),
                    'age' => (int) $request->request->get('age', 0),
                    'status' => $request->request->get('status', 'active'),
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(new \DateTime($request->request->get('createdAt', 'now'))),
                    'anonymized' => false,
                ];
                $collection->insertOne($data);
                $this->addFlash('success', 'Customer profile created successfully!');
                return $this->redirectToRoute('mongodb_customer_profile_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating profile: ' . $e->getMessage());
            }
        }

        return $this->render('customer_profile/new.html.twig', [
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_customer_profile_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_customer_profile_index');
        }

        try {
            $profile = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$profile) {
                throw $this->createNotFoundException('Customer profile not found');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading profile: ' . $e->getMessage());
            return $this->redirectToRoute('mongodb_customer_profile_index');
        }

        return $this->render('customer_profile/show.html.twig', [
            'profile' => $profile,
            'connection' => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'mongodb_customer_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_customer_profile_index');
        }

        try {
            $profile = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$profile) {
                throw $this->createNotFoundException('Customer profile not found');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading profile: ' . $e->getMessage());
            return $this->redirectToRoute('mongodb_customer_profile_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $updateData = [
                    '$set' => [
                        'email' => $request->request->get('email', ''),
                        'firstName' => $request->request->get('firstName', ''),
                        'lastName' => $request->request->get('lastName', ''),
                        'phone' => $request->request->get('phone', ''),
                        'address' => $request->request->get('address', ''),
                        'company' => $request->request->get('company', ''),
                        'username' => $request->request->get('username', ''),
                        'website' => $request->request->get('website', ''),
                        'age' => (int) $request->request->get('age', 0),
                        'status' => $request->request->get('status', 'active'),
                        'createdAt' => new \MongoDB\BSON\UTCDateTime(new \DateTime($request->request->get('createdAt', 'now'))),
                    ],
                ];
                $collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], $updateData);
                $this->addFlash('success', 'Customer profile updated successfully!');
                return $this->redirectToRoute('mongodb_customer_profile_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating profile: ' . $e->getMessage());
            }
        }

        return $this->render('customer_profile/edit.html.twig', [
            'profile' => $profile,
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_customer_profile_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_customer_profile_index');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
                $this->addFlash('success', 'Customer profile deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting profile: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('mongodb_customer_profile_index');
    }
}
