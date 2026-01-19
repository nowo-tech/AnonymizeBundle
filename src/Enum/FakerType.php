<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Enum;

/**
 * Enum for supported faker types.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
enum FakerType: string
{
    /** Email address faker */
    case EMAIL = 'email';

    /** First name faker */
    case NAME = 'name';

    /** Last name (surname) faker */
    case SURNAME = 'surname';

    /** Age faker (supports min/max options) */
    case AGE = 'age';

    /** Phone number faker */
    case PHONE = 'phone';

    /** IBAN faker (supports country option) */
    case IBAN = 'iban';

    /** Credit card number faker */
    case CREDIT_CARD = 'credit_card';

    /** Custom service faker (requires service name) */
    case SERVICE = 'service';
}
