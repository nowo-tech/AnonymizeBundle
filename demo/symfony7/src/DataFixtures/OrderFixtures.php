<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Order;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use OutOfBoundsException;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $orders = [
            // Orders with ID <= 5 (will be excluded from anonymization)
            ['orderNumber' => 'ORD-0000000001', 'shippingAddress' => '123 Main St, New York, NY 10001', 'billingAddress' => '123 Main St, New York, NY 10001', 'totalAmount' => '299.99', 'orderDate' => new DateTime('-2 months'), 'status' => 'completed', 'customerEmail' => 'customer1@example.com', 'type' => 'hr'],
            ['orderNumber' => 'ORD-0000000002', 'shippingAddress' => '456 Oak Ave, Los Angeles, CA 90001', 'billingAddress' => '456 Oak Ave, Los Angeles, CA 90001', 'totalAmount' => '599.99', 'orderDate' => new DateTime('-1 month'), 'status' => 'completed', 'customerEmail' => 'customer2@example.com', 'type' => 'sales'],
            ['orderNumber' => 'ORD-0000000003', 'shippingAddress' => '789 Pine Rd, Chicago, IL 60601', 'billingAddress' => '789 Pine Rd, Chicago, IL 60601', 'totalAmount' => '149.99', 'orderDate' => new DateTime('-3 weeks'), 'status' => 'completed', 'customerEmail' => 'customer3@example.com', 'type' => 'it'],
            ['orderNumber' => 'ORD-0000000004', 'shippingAddress' => '321 Elm St, Houston, TX 77001', 'billingAddress' => '321 Elm St, Houston, TX 77001', 'totalAmount' => '899.99', 'orderDate' => new DateTime('-2 weeks'), 'status' => 'completed', 'customerEmail' => 'customer4@example.com', 'type' => 'marketing'],
            ['orderNumber' => 'ORD-0000000005', 'shippingAddress' => '654 Maple Dr, Phoenix, AZ 85001', 'billingAddress' => '654 Maple Dr, Phoenix, AZ 85001', 'totalAmount' => '249.99', 'orderDate' => new DateTime('-1 week'), 'status' => 'completed', 'customerEmail' => 'customer5@example.com', 'type' => 'finance'],

            // Orders with ID > 5, status = completed, and type.name LIKE '%HR' (will be anonymized)
            ['orderNumber' => 'ORD-0000000006', 'shippingAddress' => '987 Cedar Ln, Philadelphia, PA 19101', 'billingAddress' => '987 Cedar Ln, Philadelphia, PA 19101', 'totalAmount' => '399.99', 'orderDate' => new DateTime('-5 days'), 'status' => 'completed', 'customerEmail' => 'customer6@example.com', 'type' => 'hr'],
            ['orderNumber' => 'ORD-0000000007', 'shippingAddress' => '147 Birch Way, San Antonio, TX 78201', 'billingAddress' => '147 Birch Way, San Antonio, TX 78201', 'totalAmount' => '549.99', 'orderDate' => new DateTime('-4 days'), 'status' => 'completed', 'customerEmail' => 'customer7@example.com', 'type' => 'hr_management'],
            ['orderNumber' => 'ORD-0000000008', 'shippingAddress' => '258 Willow St, San Diego, CA 92101', 'billingAddress' => '258 Willow St, San Diego, CA 92101', 'totalAmount' => '199.99', 'orderDate' => new DateTime('-3 days'), 'status' => 'completed', 'customerEmail' => 'customer8@example.com', 'type' => 'sales'],
            ['orderNumber' => 'ORD-0000000009', 'shippingAddress' => '369 Spruce Ave, Dallas, TX 75201', 'billingAddress' => '369 Spruce Ave, Dallas, TX 75201', 'totalAmount' => '799.99', 'orderDate' => new DateTime('-2 days'), 'status' => 'completed', 'customerEmail' => 'customer9@example.com', 'type' => 'it'],
            ['orderNumber' => 'ORD-0000000010', 'shippingAddress' => '741 Ash Blvd, San Jose, CA 95101', 'billingAddress' => '741 Ash Blvd, San Jose, CA 95101', 'totalAmount' => '349.99', 'orderDate' => new DateTime('-1 day'), 'status' => 'completed', 'customerEmail' => 'customer10@example.com', 'type' => 'hr'],

            // Orders with status != completed (will NOT be anonymized, even if type.name LIKE '%HR')
            ['orderNumber' => 'ORD-0000000011', 'shippingAddress' => '852 Poplar Rd, Austin, TX 78701', 'billingAddress' => '852 Poplar Rd, Austin, TX 78701', 'totalAmount' => '449.99', 'orderDate' => new DateTime('-1 week'), 'status' => 'pending', 'customerEmail' => 'customer11@example.com', 'type' => 'hr'],
            ['orderNumber' => 'ORD-0000000012', 'shippingAddress' => '963 Hickory Dr, Jacksonville, FL 32201', 'billingAddress' => '963 Hickory Dr, Jacksonville, FL 32201', 'totalAmount' => '299.99', 'orderDate' => new DateTime('-5 days'), 'status' => 'processing', 'customerEmail' => 'customer12@example.com', 'type' => 'hr_management'],
            ['orderNumber' => 'ORD-0000000013', 'shippingAddress' => '159 Sycamore Ln, Fort Worth, TX 76101', 'billingAddress' => '159 Sycamore Ln, Fort Worth, TX 76101', 'totalAmount' => '649.99', 'orderDate' => new DateTime('-3 days'), 'status' => 'shipped', 'customerEmail' => 'customer13@example.com', 'type' => 'sales'],
        ];

        foreach ($orders as $orderData) {
            $order = new Order();
            $order->setOrderNumber($orderData['orderNumber']);
            $order->setShippingAddress($orderData['shippingAddress']);
            $order->setBillingAddress($orderData['billingAddress']);
            $order->setTotalAmount($orderData['totalAmount']);
            $order->setOrderDate($orderData['orderDate']);
            $order->setStatus($orderData['status']);
            $order->setCustomerEmail($orderData['customerEmail']);

            // Set type relationship if reference exists
            if (isset($orderData['type'])) {
                try {
                    $order->setType($this->getReference('type_' . $orderData['type'], \App\Entity\Type::class));
                } catch (OutOfBoundsException $e) {
                    // Reference doesn't exist, skip setting type
                }
            }

            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TypeFixtures::class,
        ];
    }
}
