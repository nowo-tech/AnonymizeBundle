<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mongodb/device-info')]
class DeviceInfoController extends AbstractController
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
        return $database->selectCollection('device_infos');
    }

    #[Route('/', name: 'mongodb_device_info_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!extension_loaded('mongodb') || (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client'))) {
            $errorMsg = 'MongoDB PHP extension or MongoDB\Client class not found.';
            $this->addFlash('error', $errorMsg);
            return $this->render('device_info/index.html.twig', [
                'devices' => [],
                'connection' => 'mongodb',
                'error' => $errorMsg,
            ]);
        }

        $collection = $this->getCollection();
        if (!$collection) {
            $errorMsg = 'MongoDB connection not available.';
            $this->addFlash('error', $errorMsg);
            return $this->render('device_info/index.html.twig', [
                'devices' => [],
                'connection' => 'mongodb',
                'error' => $errorMsg,
            ]);
        }

        try {
            $devices = $collection->find([], ['sort' => ['lastSeen' => -1]]);
            $devicesArray = [];
            foreach ($devices as $device) {
                $devicesArray[] = $device;
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading devices: ' . $e->getMessage());
            $devicesArray = [];
        }

        return $this->render('device_info/index.html.twig', [
            'devices' => $devicesArray,
            'connection' => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/new', name: 'mongodb_device_info_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $collection = $this->getCollection();
            if (!$collection) {
                $this->addFlash('error', 'MongoDB connection not available.');
                return $this->redirectToRoute('mongodb_device_info_index');
            }

            try {
                $data = [
                    'deviceId' => $request->request->get('deviceId', ''),
                    'ipAddress' => $request->request->get('ipAddress', ''),
                    'macAddress' => $request->request->get('macAddress', ''),
                    'deviceHash' => $request->request->get('deviceHash', ''),
                    'location' => $request->request->get('location', ''),
                    'themeColor' => $request->request->get('themeColor', '#667eea'),
                    'deviceName' => $request->request->get('deviceName', ''),
                    'osVersion' => $request->request->get('osVersion', ''),
                    'browserVersion' => $request->request->get('browserVersion', ''),
                    'isActive' => $request->request->get('isActive') === '1',
                    'lastSeen' => new \MongoDB\BSON\UTCDateTime(new \DateTime($request->request->get('lastSeen', 'now'))),
                    'anonymized' => false,
                ];
                $collection->insertOne($data);
                $this->addFlash('success', 'Device info created successfully!');
                return $this->redirectToRoute('mongodb_device_info_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating device: ' . $e->getMessage());
            }
        }

        return $this->render('device_info/new.html.twig', [
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_device_info_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_device_info_index');
        }

        try {
            $device = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$device) {
                throw $this->createNotFoundException('Device info not found');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading device: ' . $e->getMessage());
            return $this->redirectToRoute('mongodb_device_info_index');
        }

        return $this->render('device_info/show.html.twig', [
            'device' => $device,
            'connection' => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'mongodb_device_info_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_device_info_index');
        }

        try {
            $device = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$device) {
                throw $this->createNotFoundException('Device info not found');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error loading device: ' . $e->getMessage());
            return $this->redirectToRoute('mongodb_device_info_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $updateData = [
                    '$set' => [
                        'deviceId' => $request->request->get('deviceId', ''),
                        'ipAddress' => $request->request->get('ipAddress', ''),
                        'macAddress' => $request->request->get('macAddress', ''),
                        'deviceHash' => $request->request->get('deviceHash', ''),
                        'location' => $request->request->get('location', ''),
                        'themeColor' => $request->request->get('themeColor', '#667eea'),
                        'deviceName' => $request->request->get('deviceName', ''),
                        'osVersion' => $request->request->get('osVersion', ''),
                        'browserVersion' => $request->request->get('browserVersion', ''),
                        'isActive' => $request->request->get('isActive') === '1',
                        'lastSeen' => new \MongoDB\BSON\UTCDateTime(new \DateTime($request->request->get('lastSeen', 'now'))),
                    ],
                ];
                $collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], $updateData);
                $this->addFlash('success', 'Device info updated successfully!');
                return $this->redirectToRoute('mongodb_device_info_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating device: ' . $e->getMessage());
            }
        }

        return $this->render('device_info/edit.html.twig', [
            'device' => $device,
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_device_info_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');
            return $this->redirectToRoute('mongodb_device_info_index');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
                $this->addFlash('success', 'Device info deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting device: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('mongodb_device_info_index');
    }
}
