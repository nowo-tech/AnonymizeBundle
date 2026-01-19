<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            ['name' => 'Laptop Pro 15', 'description' => 'High-performance laptop with 16GB RAM and 512GB SSD', 'price' => '1299.99', 'imageUrl' => 'https://example.com/products/laptop-pro-15.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-6 months')],
            ['name' => 'Wireless Mouse', 'description' => 'Ergonomic wireless mouse with long battery life', 'price' => '29.99', 'imageUrl' => 'https://example.com/products/mouse.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-3 months')],
            ['name' => 'Mechanical Keyboard', 'description' => 'RGB mechanical keyboard with cherry switches', 'price' => '149.99', 'imageUrl' => 'https://example.com/products/keyboard.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-2 months')],
            ['name' => '4K Monitor', 'description' => '27-inch 4K UHD monitor with HDR support', 'price' => '599.99', 'imageUrl' => 'https://example.com/products/monitor.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-1 year')],
            ['name' => 'USB-C Hub', 'description' => 'Multi-port USB-C hub with HDMI and SD card reader', 'price' => '49.99', 'imageUrl' => 'https://example.com/products/hub.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-4 months')],
            ['name' => 'Webcam HD', 'description' => '1080p HD webcam with auto-focus and noise cancellation', 'price' => '79.99', 'imageUrl' => 'https://example.com/products/webcam.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-5 months')],
            ['name' => 'Gaming Headset', 'description' => '7.1 surround sound gaming headset with RGB lighting', 'price' => '129.99', 'imageUrl' => 'https://example.com/products/headset.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-8 months')],
            ['name' => 'External SSD 1TB', 'description' => 'Portable external SSD with USB 3.2 Gen 2', 'price' => '199.99', 'imageUrl' => 'https://example.com/products/ssd.jpg', 'status' => 'active', 'createdAt' => new \DateTime('-7 months')],
            ['name' => 'Old Product Model', 'description' => 'This product is no longer available', 'price' => '99.99', 'imageUrl' => null, 'status' => 'discontinued', 'createdAt' => new \DateTime('-2 years')],
            ['name' => 'Pending Product', 'description' => 'Product awaiting approval', 'price' => '199.99', 'imageUrl' => null, 'status' => 'pending', 'createdAt' => new \DateTime('-1 week')],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setImageUrl($productData['imageUrl']);
            $product->setStatus($productData['status']);
            $product->setCreatedAt($productData['createdAt']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
