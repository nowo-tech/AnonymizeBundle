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

#[Route('/mongodb/transaction-log')]
class TransactionLogController extends AbstractController
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

        return $database->selectCollection('transaction_logs');
    }

    #[Route('/', name: 'mongodb_transaction_log_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!extension_loaded('mongodb') || (!class_exists('\MongoDB\Client') && !class_exists('MongoDB\Client'))) {
            $errorMsg = 'MongoDB PHP extension or MongoDB\Client class not found.';
            $this->addFlash('error', $errorMsg);

            return $this->render('transaction_log/index.html.twig', [
                'transactions' => [],
                'connection'   => 'mongodb',
                'error'        => $errorMsg,
            ]);
        }

        $collection = $this->getCollection();
        if (!$collection) {
            $errorMsg = 'MongoDB connection not available.';
            $this->addFlash('error', $errorMsg);

            return $this->render('transaction_log/index.html.twig', [
                'transactions' => [],
                'connection'   => 'mongodb',
                'error'        => $errorMsg,
            ]);
        }

        try {
            $transactions      = $collection->find([], ['sort' => ['transactionDate' => -1]]);
            $transactionsArray = [];
            foreach ($transactions as $transaction) {
                $transactionsArray[] = $transaction;
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading transactions: ' . $e->getMessage());
            $transactionsArray = [];
        }

        return $this->render('transaction_log/index.html.twig', [
            'transactions'        => $transactionsArray,
            'connection'          => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/new', name: 'mongodb_transaction_log_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $collection = $this->getCollection();
            if (!$collection) {
                $this->addFlash('error', 'MongoDB connection not available.');

                return $this->redirectToRoute('mongodb_transaction_log_index');
            }

            try {
                $data = [
                    'transactionId'   => $request->request->get('transactionId', ''),
                    'customerEmail'   => $request->request->get('customerEmail', ''),
                    'iban'            => $request->request->get('iban', ''),
                    'creditCard'      => $request->request->get('creditCard', ''),
                    'maskedCard'      => $request->request->get('maskedCard', ''),
                    'amount'          => (float) $request->request->get('amount', 0),
                    'currency'        => $request->request->get('currency', 'EUR'),
                    'transactionHash' => $request->request->get('transactionHash', ''),
                    'status'          => $request->request->get('status', 'pending'),
                    'transactionDate' => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('transactionDate', 'now'))),
                    'anonymized'      => false,
                ];
                $collection->insertOne($data);
                $this->addFlash('success', 'Transaction log created successfully!');

                return $this->redirectToRoute('mongodb_transaction_log_index');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error creating transaction: ' . $e->getMessage());
            }
        }

        return $this->render('transaction_log/new.html.twig', [
            'connection' => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_transaction_log_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_transaction_log_index');
        }

        try {
            $transaction = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$transaction) {
                throw $this->createNotFoundException('Transaction log not found');
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading transaction: ' . $e->getMessage());

            return $this->redirectToRoute('mongodb_transaction_log_index');
        }

        return $this->render('transaction_log/show.html.twig', [
            'transaction'         => $transaction,
            'connection'          => 'mongodb',
            'hasAnonymizedColumn' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'mongodb_transaction_log_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_transaction_log_index');
        }

        try {
            $transaction = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
            if (!$transaction) {
                throw $this->createNotFoundException('Transaction log not found');
            }
        } catch (Exception $e) {
            $this->addFlash('error', 'Error loading transaction: ' . $e->getMessage());

            return $this->redirectToRoute('mongodb_transaction_log_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $updateData = [
                    '$set' => [
                        'transactionId'   => $request->request->get('transactionId', ''),
                        'customerEmail'   => $request->request->get('customerEmail', ''),
                        'iban'            => $request->request->get('iban', ''),
                        'creditCard'      => $request->request->get('creditCard', ''),
                        'maskedCard'      => $request->request->get('maskedCard', ''),
                        'amount'          => (float) $request->request->get('amount', 0),
                        'currency'        => $request->request->get('currency', 'EUR'),
                        'transactionHash' => $request->request->get('transactionHash', ''),
                        'status'          => $request->request->get('status', 'pending'),
                        'transactionDate' => new \MongoDB\BSON\UTCDateTime(new DateTime($request->request->get('transactionDate', 'now'))),
                    ],
                ];
                $collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], $updateData);
                $this->addFlash('success', 'Transaction log updated successfully!');

                return $this->redirectToRoute('mongodb_transaction_log_index');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error updating transaction: ' . $e->getMessage());
            }
        }

        return $this->render('transaction_log/edit.html.twig', [
            'transaction' => $transaction,
            'connection'  => 'mongodb',
        ]);
    }

    #[Route('/{id}', name: 'mongodb_transaction_log_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        $collection = $this->getCollection();
        if (!$collection) {
            $this->addFlash('error', 'MongoDB connection not available.');

            return $this->redirectToRoute('mongodb_transaction_log_index');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
                $this->addFlash('success', 'Transaction log deleted successfully!');
            } catch (Exception $e) {
                $this->addFlash('error', 'Error deleting transaction: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('mongodb_transaction_log_index');
    }
}
