<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function extension_loaded;

#[Route('/mongodb/analytics-event')]
class AnalyticsEventController extends AbstractController
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
        } catch (Exception $e) {
            return null;
        }
    }

    private function getCollection()
    {
        $client = $this->getMongoClient();
        if (!$client) {
            return null;
        }
        $mongodbUrl   = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
        $parsedUrl    = parse_url(str_replace('mongodb://', 'http://', $mongodbUrl));
        $databaseName = trim($parsedUrl['path'] ?? 'anonymize_demo', '/');
        $databaseName = explode('?', $databaseName)[0];
        $database     = $client->selectDatabase($databaseName);

        return $database->selectCollection('analytics_events');
    }

    #[Route('/', name: 'mongodb_analytics_event_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!extension_loaded('mongodb') || (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client'))) {
            $errorMsg = 'MongoDB PHP extension or MongoDB\Client class not found.';
            $this->addFlash('error', $errorMsg);

            return $this->render('analytics_event/index.html.twig', [
                'events'     => [],
                'connection' => 'mongodb',
                'error'      => $errorMsg,
            ]);
        }

        $collection = $this->getCollection();
        if (!$collection) {
            $errorMsg = 'MongoDB connection not available.';
            $this->addFlash('error', $errorMsg);

            return $this->render('analytics_event/index.html.twig', [
                'events'     => [],
                'connection' => 'mongodb',
                'error'      => $errorMsg,
            ]);
        }

        try {
            $events      = $collection->find([], ['sort' => ['timestamp' => -1]]);
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = $event;
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading events: ' . $e->getMessage());
            $eventsArray = [];
        }

        return $this->render('analytics_event/index.html.twig', [
            'events'              => $eventsArray,
            'connection'          => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/new', name: 'mongodb_analytics_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $collection = $this->getCollection();
            if (!$collection) {
                $this->addFlash('error', 'MongoDB connection not available.');

                return $this->redirectToRoute('mongodb_analytics_event_index');
            }

            try {
                $data = [
                    'eventId'            => $request->request->get('eventId', ''),
                    'eventType'          => $request->request->get('eventType', 'page_view'),
                    'country'            => $request->request->get('country', ''),
                    'language'           => $request->request->get('language', ''),
                    'eventData'          => $request->request->get('eventData', '{}'),
                    'description'        => $request->request->get('description', ''),
                    'userIdHash'         => $request->request->get('userIdHash', ''),
                    'category'           => $request->request->get('category', ''),
                    'dataClassification' => $request->request->get('dataClassification', 'ANONYMIZED'),
                    'timestamp'          => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('timestamp', 'now'))),
                    'anonymized'         => false,
                ];
                $collection->insertOne($data);
                $this->addFlash('success', 'Analytics event created successfully!');

                return $this->redirectToRoute('mongodb_analytics_event_index');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error creating event: ' . $e->getMessage());
            }
        }

        return $this->render('analytics_event/new.html.twig', [
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_analytics_event_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_analytics_event_index');
        }

        try {
            $event = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$event) {
                throw $this->createNotFoundException('Analytics event not found');
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading event: ' . $e->getMessage());

            return $this->redirectToRoute('mongodb_analytics_event_index');
        }

        return $this->render('analytics_event/show.html.twig', [
            'event'               => $event,
            'connection'          => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'mongodb_analytics_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_analytics_event_index');
        }

        try {
            $event = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$event) {
                throw $this->createNotFoundException('Analytics event not found');
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading event: ' . $e->getMessage());

            return $this->redirectToRoute('mongodb_analytics_event_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $updateData = [
                    '$set' => [
                        'eventId'            => $request->request->get('eventId', ''),
                        'eventType'          => $request->request->get('eventType', 'page_view'),
                        'country'            => $request->request->get('country', ''),
                        'language'           => $request->request->get('language', ''),
                        'eventData'          => $request->request->get('eventData', '{}'),
                        'description'        => $request->request->get('description', ''),
                        'userIdHash'         => $request->request->get('userIdHash', ''),
                        'category'           => $request->request->get('category', ''),
                        'dataClassification' => $request->request->get('dataClassification', 'ANONYMIZED'),
                        'timestamp'          => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('timestamp', 'now'))),
                    ],
                ];
                $collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], $updateData);
                $this->addFlash('success', 'Analytics event updated successfully!');

                return $this->redirectToRoute('mongodb_analytics_event_index');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error updating event: ' . $e->getMessage());
            }
        }

        return $this->render('analytics_event/edit.html.twig', [
            'event'      => $event,
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_analytics_event_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_analytics_event_index');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
                $this->addFlash('success', 'Analytics event deleted successfully!');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error deleting event: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('mongodb_analytics_event_index');
    }
}
