<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EmailSignature;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmailSignatureFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $signatures = [
            [
                'email' => 'john.doe@example.com',
                'signature' => '<div style="font-family: Arial, sans-serif; font-size: 12px;">
                    <p><strong>John Doe</strong><br>
                    Senior Developer<br>
                    Tech Solutions Inc.</p>
                    <p>Phone: <a href="tel:+34612345678">+34 612 345 678</a><br>
                    Email: <a href="mailto:john.doe@example.com">john.doe@example.com</a><br>
                    Website: <a href="https://www.techsolutions.com">www.techsolutions.com</a></p>
                    <p>Best regards,<br>John</p>
                </div>',
                'emailBody' => '<p>Dear Customer,</p><p>Thank you for your interest in our services. We are pleased to inform you that your request has been processed successfully.</p><p>If you have any questions, please do not hesitate to contact us.</p>',
                'senderName' => 'John Doe',
                'sentAt' => new \DateTime('-30 days'),
            ],
            [
                'email' => 'jane.smith@company.com',
                'signature' => '<div>
                    <p><strong>Jane Smith</strong><br>
                    Marketing Director<br>
                    Global Marketing Ltd.</p>
                    <p>Phone: +34 698 765 432<br>
                    Email: jane.smith@company.com<br>
                    Website: www.globalmarketing.com</p>
                </div>',
                'emailBody' => '<p>Hello,</p><p>We wanted to reach out regarding your recent inquiry. Our team is ready to assist you with any questions you may have.</p>',
                'senderName' => 'Jane Smith',
                'sentAt' => new \DateTime('-15 days'),
            ],
            [
                'email' => 'bob.wilson@startup.io',
                'signature' => '<div style="color: #333;">
                    <p><strong>Bob Wilson</strong><br>
                    CEO & Founder<br>
                    Startup Innovations</p>
                    <p>Mobile: <a href="tel:+34655512345">+34 655 512 345</a><br>
                    Email: <a href="mailto:bob.wilson@startup.io">bob.wilson@startup.io</a></p>
                    <p>Follow us on <a href="https://twitter.com/startup">Twitter</a> | <a href="https://linkedin.com/company/startup">LinkedIn</a></p>
                </div>',
                'emailBody' => '<p>Hi there,</p><p>I hope this email finds you well. I wanted to personally thank you for your interest in our company.</p><p>We are excited about the possibility of working together.</p>',
                'senderName' => 'Bob Wilson',
                'sentAt' => new \DateTime('-7 days'),
            ],
            [
                'email' => 'alice.brown@corp.com',
                'signature' => '<div>
                    <p><strong>Alice Brown</strong><br>
                    HR Manager<br>
                    Corporate Solutions</p>
                    <p>Direct Line: +34 611 222 333<br>
                    Email: alice.brown@corp.com</p>
                    <p>This email and any attachments are confidential and may be legally privileged.</p>
                </div>',
                'emailBody' => '<p>Dear Applicant,</p><p>Thank you for applying to our position. We have reviewed your application and would like to schedule an interview.</p>',
                'senderName' => 'Alice Brown',
                'sentAt' => new \DateTime('-3 days'),
            ],
            [
                'email' => 'charlie.davis@agency.net',
                'signature' => '<div style="font-size: 11px; color: #666;">
                    <p><strong>Charlie Davis</strong><br>
                    Creative Director<br>
                    Design Agency</p>
                    <ul>
                        <li>Phone: +34 644 555 666</li>
                        <li>Email: charlie.davis@agency.net</li>
                        <li>Portfolio: <a href="https://portfolio.charlie.com">portfolio.charlie.com</a></li>
                    </ul>
                </div>',
                'emailBody' => '<p>Greetings,</p><p>I am writing to discuss a potential collaboration opportunity. We believe there is great potential for a partnership between our organizations.</p>',
                'senderName' => 'Charlie Davis',
                'sentAt' => new \DateTime('-1 day'),
            ],
            [
                'email' => 'david.miller@consulting.com',
                'signature' => '<div>
                    <p><strong>David Miller</strong><br>
                    Senior Consultant<br>
                    Business Consulting Group</p>
                    <p>Office: +34 677 888 999<br>
                    Mobile: +34 688 999 000<br>
                    Email: david.miller@consulting.com</p>
                    <p>Available: Monday - Friday, 9:00 - 18:00 CET</p>
                </div>',
                'emailBody' => '<p>Dear Client,</p><p>Following our recent conversation, I am pleased to provide you with the requested information. Please find the details attached.</p><p>Should you require any clarification, I am at your disposal.</p>',
                'senderName' => 'David Miller',
                'sentAt' => new \DateTime('-5 days'),
            ],
            [
                'email' => 'emma.jones@finance.bank',
                'signature' => '<div style="border-top: 2px solid #0066cc; padding-top: 10px;">
                    <p><strong>Emma Jones</strong><br>
                    Financial Advisor<br>
                    Trust Bank International</p>
                    <p>Phone: +34 622 333 444<br>
                    Email: <a href="mailto:emma.jones@finance.bank">emma.jones@finance.bank</a><br>
                    Website: <a href="https://www.trustbank.com">www.trustbank.com</a></p>
                    <p><small>This message is confidential and intended only for the addressee.</small></p>
                </div>',
                'emailBody' => '<p>Dear Valued Customer,</p><p>We are writing to inform you about important updates to your account. Please review the attached statement at your earliest convenience.</p>',
                'senderName' => 'Emma Jones',
                'sentAt' => new \DateTime('-10 days'),
            ],
            [
                'email' => 'frank.taylor@legal.firm',
                'signature' => '<div>
                    <p><strong>Frank Taylor, Esq.</strong><br>
                    Partner<br>
                    Legal Associates LLP</p>
                    <p>Direct: +34 633 444 555<br>
                    Reception: +34 911 222 333<br>
                    Email: frank.taylor@legal.firm</p>
                    <p>Address: 123 Legal Street, Madrid, Spain</p>
                </div>',
                'emailBody' => '<p>Dear Sir/Madam,</p><p>I am writing in reference to the matter we discussed. Please find enclosed the relevant documentation for your review.</p><p>We look forward to your response.</p>',
                'senderName' => 'Frank Taylor',
                'sentAt' => new \DateTime('-20 days'),
            ],
        ];

        foreach ($signatures as $signatureData) {
            $signature = new EmailSignature();
            $signature->setEmail($signatureData['email']);
            $signature->setSignature($signatureData['signature']);
            $signature->setEmailBody($signatureData['emailBody']);
            $signature->setSenderName($signatureData['senderName']);
            $signature->setSentAt($signatureData['sentAt']);
            $signature->setAnonymized(false);

            $manager->persist($signature);
        }

        $manager->flush();
    }
}
