<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Device info form (MongoDB demo, REQ-TWIG-005).
 */
final class DeviceInfoDocumentType extends AbstractMongoDocumentType
{
    public static function fields(): array
    {
        $c = ['class' => 'form-control'];
        $s = ['class' => 'form-select'];

        return [
            'deviceId'       => [TextType::class, ['label' => 'Device ID (UUID)', 'required' => false, 'attr' => $c + ['placeholder' => '550e8400-e29b-41d4-a716-446655440000']], 'string', ''],
            'deviceName'     => [TextType::class, ['label' => 'Device Name', 'required' => false, 'attr' => $c], 'string', ''],
            'ipAddress'      => [TextType::class, ['label' => 'IP Address', 'required' => false, 'attr' => $c + ['placeholder' => '192.168.1.1']], 'string', ''],
            'macAddress'     => [TextType::class, ['label' => 'MAC Address', 'required' => false, 'attr' => $c + ['placeholder' => '00:11:22:33:44:55']], 'string', ''],
            'deviceHash'     => [TextType::class, ['label' => 'Device Hash', 'required' => false, 'attr' => $c], 'string', ''],
            'location'       => [TextType::class, ['label' => 'Location (Coordinates)', 'required' => false, 'attr' => $c + ['placeholder' => '40.4168, -3.7038']], 'string', ''],
            'themeColor'     => [ColorType::class, ['label' => 'Theme Color', 'required' => false, 'attr' => ['class' => 'form-control form-control-color']], 'string', '#667eea'],
            'isActive'       => [ChoiceType::class, ['label' => 'Is Active', 'required' => false, 'attr' => $s, 'choices' => ['Yes' => '1', 'No' => '0'], 'placeholder' => false], 'bool01', '1'],
            'osVersion'      => [TextType::class, ['label' => 'OS Version', 'required' => false, 'attr' => $c + ['placeholder' => 'Windows 11']], 'string', ''],
            'browserVersion' => [TextType::class, ['label' => 'Browser Version', 'required' => false, 'attr' => $c + ['placeholder' => 'Chrome 120.0']], 'string', ''],
            'lastSeen'       => [TextType::class, ['label' => 'Last Seen', 'required' => false, 'attr' => $c], 'datetime', null],
        ];
    }
}
