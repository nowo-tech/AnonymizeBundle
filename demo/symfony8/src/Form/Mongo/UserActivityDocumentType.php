<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * User activity form (MongoDB demo, REQ-TWIG-005).
 */
final class UserActivityDocumentType extends AbstractMongoDocumentType
{
    public static function fields(): array
    {
        return [
            'userEmail' => [EmailType::class, ['label' => 'User Email *', 'required' => true], 'string', ''],
            'userName'  => [TextType::class, ['label' => 'User Name *', 'required' => true], 'string', ''],
            'ipAddress' => [TextType::class, ['label' => 'IP Address *', 'required' => true], 'string', ''],
            'action'    => [ChoiceType::class, ['label' => 'Action *', 'required' => true, 'choices' => ['Login' => 'login', 'Logout' => 'logout', 'View Page' => 'view_page', 'Update Profile' => 'update_profile', 'Create Order' => 'create_order', 'Cancel Order' => 'cancel_order', 'Add to Cart' => 'add_to_cart', 'Remove from Cart' => 'remove_from_cart'], 'placeholder' => 'Select an action'], 'string', ''],
            'timestamp' => [TextType::class, ['label' => 'Timestamp *', 'required' => true], 'datetime', null],
            'userAgent' => [TextType::class, ['label' => 'User Agent', 'required' => false], 'string', '', ['metadata', 'userAgent']],
            'sessionId' => [TextType::class, ['label' => 'Session ID', 'required' => false], 'string', '', ['metadata', 'sessionId']],
            'referrer'  => [UrlType::class, ['label' => 'Referrer', 'required' => false, 'default_protocol' => null], 'string', '', ['metadata', 'referrer']],
        ];
    }
}
