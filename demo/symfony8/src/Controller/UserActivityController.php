<?php

declare(strict_types=1);

namespace App\Controller;

// Note: MongoDB client requires mongodb/mongodb package or mongodb PHP extension
// For now, we'll check if it's available at runtime
// TODO: Add mongodb/mongodb to composer.json when MongoDB ODM support is added
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function extension_loaded;

#[Route('/mongodb/user-activity')]
class UserActivityController extends AbstractController
{
    private function getMongoClient()
    {
        // Check if MongoDB PHP extension is loaded
        if (!extension_loaded('mongodb')) {
            return null;
        }

        // Check if MongoDB\Client class exists (from mongodb/mongodb package)
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
        } catch (Exception $e) {
            return null;
        }
    }

    private function getDatabase()
    {
        $client = $this->getMongoClient();
        if (!$client) {
            return null;
        }

        // Parse MongoDB URL to get database name
        $mongodbUrl   = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
        $parsedUrl    = parse_url(str_replace('mongodb://', 'http://', $mongodbUrl));
        $databaseName = trim($parsedUrl['path'] ?? 'anonymize_demo', '/');
        $databaseName = explode('?', $databaseName)[0]; // Remove query params

        return $client->selectDatabase($databaseName);
    }

    private function getCollection()
    {
        $database = $this->getDatabase();
        if (!$database) {
            return null;
        }

        return $database->selectCollection('user_activities');
    }

    #[Route('/', name: 'mongodb_user_activity_index', methods: ['GET'])]
    public function index(): Response
    {
        // Check if MongoDB extension is available
        if (!extension_loaded('mongodb')) {
            $errorMsg = 'MongoDB PHP extension is not loaded. ';
            $errorMsg .= 'The extension should be installed in the Dockerfile. ';
            $errorMsg .= 'Please rebuild the container: docker-compose build --no-cache php';
            $this->addFlash('error', $errorMsg);

            return $this->render('user_activity/index.html.twig', [
                'activities' => [],
                'connection' => 'mongodb',
                'error'      => $errorMsg,
            ]);
        }

        // Check if MongoDB\Client class exists
        if (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client')) {
            $errorMsg = 'MongoDB\Client class not found. ';
            $errorMsg .= 'The mongodb/mongodb package should be in composer.json. ';
            $errorMsg .= 'Please run: docker-compose exec php composer install';
            $this->addFlash('error', $errorMsg);

            return $this->render('user_activity/index.html.twig', [
                'activities' => [],
                'connection' => 'mongodb',
                'error'      => $errorMsg,
            ]);
        }

        $collection = $this->getCollection();

        if (!$collection) {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
            $errorMsg   = 'MongoDB connection not available.';
            if (!$mongodbUrl) {
                $errorMsg .= ' MONGODB_URL environment variable is not set.';
            } else {
                $errorMsg .= ' Please check MONGODB_URL and MongoDB service.';
            }
            $this->addFlash('error', $errorMsg);

            return $this->render('user_activity/index.html.twig', [
                'activities' => [],
                'connection' => 'mongodb',
                'error'      => $errorMsg,
            ]);
        }

        try {
            $activities      = $collection->find([], ['sort' => ['timestamp' => -1]]);
            $activitiesArray = [];
            foreach ($activities as $activity) {
                $activitiesArray[] = $activity;
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading activities: ' . $e->getMessage());
            $activitiesArray = [];
        }

        return $this->render('user_activity/index.html.twig', [
            'activities'          => $activitiesArray,
            'connection'          => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/new', name: 'mongodb_user_activity_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $collection = $this->getCollection();

            if (!$collection) {
                $this->addFlash('error', 'MongoDB connection not available.');

                return $this->redirectToRoute('mongodb_user_activity_index');
            }

            try {
                $data = [
                    'userEmail' => $request->request->get('userEmail', ''),
                    'userName'  => $request->request->get('userName', ''),
                    'ipAddress' => $request->request->get('ipAddress', ''),
                    'action'    => $request->request->get('action', ''),
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('timestamp', 'now'))),
                    'metadata'  => [
                        'userAgent' => $request->request->get('userAgent', ''),
                        'sessionId' => $request->request->get('sessionId', ''),
                        'referrer'  => $request->request->get('referrer', ''),
                    ],
                    'anonymized' => false, // Track anonymization status
                ];

                $collection->insertOne($data);
                $this->addFlash('success', 'User activity created successfully!');

                return $this->redirectToRoute('mongodb_user_activity_index');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error creating activity: ' . $e->getMessage());
            }
        }

        return $this->render('user_activity/new.html.twig', [
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_user_activity_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $collection = $this->getCollection();

        if (!$collection) {
            throw $this->createNotFoundException('MongoDB connection not available');
        }

        try {
            $activity = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$activity) {
                throw $this->createNotFoundException('User activity not found');
            }
        } catch (Exception $e) {
            throw $this->createNotFoundException('User activity not found: ' . $e->getMessage());
        }

        return $this->render('user_activity/show.html.twig', [
            'activity'   => $activity,
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}/edit', name: 'mongodb_user_activity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $collection = $this->getCollection();

        if (!$collection) {
            throw $this->createNotFoundException('MongoDB connection not available');
        }

        try {
            $activity = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

            if (!$activity) {
                throw $this->createNotFoundException('User activity not found');
            }

            if ($request->isMethod('POST')) {
                $updateData = [
                    'userEmail' => $request->request->get('userEmail', ''),
                    'userName'  => $request->request->get('userName', ''),
                    'ipAddress' => $request->request->get('ipAddress', ''),
                    'action'    => $request->request->get('action', ''),
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('timestamp', 'now'))),
                    'metadata'  => [
                        'userAgent' => $request->request->get('userAgent', ''),
                        'sessionId' => $request->request->get('sessionId', ''),
                        'referrer'  => $request->request->get('referrer', ''),
                    ],
                    // Note: anonymized field is preserved (not updated via form)
                ];

                $collection->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($id)],
                    ['$set' => $updateData],
                );

                $this->addFlash('success', 'User activity updated successfully!');

                return $this->redirectToRoute('mongodb_user_activity_index');
            }
        } catch (Exception $e) {
            throw $this->createNotFoundException('User activity not found: ' . $e->getMessage());
        }

        return $this->render('user_activity/edit.html.twig', [
            'activity'   => $activity,
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_user_activity_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        $collection = $this->getCollection();

        if (!$collection) {
            throw $this->createNotFoundException('MongoDB connection not available');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $result = $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

                if ($result->getDeletedCount() > 0) {
                    $this->addFlash('success', 'User activity deleted successfully!');
                } else {
                    $this->addFlash('error', 'User activity not found');
                }
            } catch (Exception $e) {
                $this->addFlash('error', 'Error deleting activity: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('mongodb_user_activity_index');
    }
}
