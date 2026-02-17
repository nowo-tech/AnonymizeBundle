<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Invoice;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InvoiceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $invoices = [
            ['invoiceNumber' => 'INV-000000000001', 'companyName' => 'Acme Corporation', 'companyAddress' => '100 Business Park, New York, NY 10001', 'bankAccount' => 'ES9121000418450200051332', 'creditCard' => '4532123456789012', 'amount' => '1250.50', 'issueDate' => new DateTime('-3 months'), 'dueDate' => new DateTime('+27 days'), 'status' => 'paid'],
            ['invoiceNumber' => 'INV-000000000002', 'companyName' => 'Tech Solutions Inc.', 'companyAddress' => '200 Innovation Drive, San Francisco, CA 94102', 'bankAccount' => 'ES9121000418450200051333', 'creditCard' => '5555123456789012', 'amount' => '3450.75', 'issueDate' => new DateTime('-2 months'), 'dueDate' => new DateTime('+28 days'), 'status' => 'pending'],
            ['invoiceNumber' => 'INV-000000000003', 'companyName' => 'Global Services LLC', 'companyAddress' => '300 Commerce Blvd, Chicago, IL 60601', 'bankAccount' => 'ES9121000418450200051334', 'creditCard' => '4111111111111111', 'amount' => '890.25', 'issueDate' => new DateTime('-1 month'), 'dueDate' => new DateTime('+29 days'), 'status' => 'paid'],
            ['invoiceNumber' => 'INV-000000000004', 'companyName' => 'Digital Marketing Corp', 'companyAddress' => '400 Media Street, Los Angeles, CA 90001', 'bankAccount' => 'ES9121000418450200051335', 'creditCard' => '378282246310005', 'amount' => '5678.90', 'issueDate' => new DateTime('-3 weeks'), 'dueDate' => new DateTime('+7 days'), 'status' => 'overdue'],
            ['invoiceNumber' => 'INV-000000000005', 'companyName' => 'Software Development Ltd.', 'companyAddress' => '500 Code Avenue, Seattle, WA 98101', 'bankAccount' => 'ES9121000418450200051336', 'creditCard' => '6011111111111117', 'amount' => '2345.67', 'issueDate' => new DateTime('-2 weeks'), 'dueDate' => new DateTime('+16 days'), 'status' => 'pending'],
            ['invoiceNumber' => 'INV-000000000006', 'companyName' => 'Consulting Group Inc.', 'companyAddress' => '600 Strategy Way, Boston, MA 02101', 'bankAccount' => 'ES9121000418450200051337', 'creditCard' => '5105105105105100', 'amount' => '4567.89', 'issueDate' => new DateTime('-1 week'), 'dueDate' => new DateTime('+23 days'), 'status' => 'paid'],
            ['invoiceNumber' => 'INV-000000000007', 'companyName' => 'E-commerce Solutions LLC', 'companyAddress' => '700 Online Plaza, Miami, FL 33101', 'bankAccount' => 'ES9121000418450200051338', 'creditCard' => '4242424242424242', 'amount' => '1234.56', 'issueDate' => new DateTime('-5 days'), 'dueDate' => new DateTime('+25 days'), 'status' => 'pending'],
            ['invoiceNumber' => 'INV-000000000008', 'companyName' => 'Cloud Services Corp', 'companyAddress' => '800 Cloud Drive, Austin, TX 78701', 'bankAccount' => 'ES9121000418450200051339', 'creditCard' => '371449635398431', 'amount' => '7890.12', 'issueDate' => new DateTime('-4 days'), 'dueDate' => new DateTime('+26 days'), 'status' => 'paid'],
        ];

        foreach ($invoices as $invoiceData) {
            $invoice = new Invoice();
            $invoice->setInvoiceNumber($invoiceData['invoiceNumber']);
            $invoice->setCompanyName($invoiceData['companyName']);
            $invoice->setCompanyAddress($invoiceData['companyAddress']);
            $invoice->setBankAccount($invoiceData['bankAccount']);
            $invoice->setCreditCard($invoiceData['creditCard']);
            $invoice->setAmount($invoiceData['amount']);
            $invoice->setIssueDate($invoiceData['issueDate']);
            $invoice->setDueDate($invoiceData['dueDate']);
            $invoice->setStatus($invoiceData['status']);

            $manager->persist($invoice);
        }

        $manager->flush();
    }
}
