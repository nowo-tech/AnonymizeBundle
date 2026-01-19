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

    /** Address faker (supports country, format, postal code options) */
    case ADDRESS = 'address';

    /** Date faker (supports min_date, max_date, format, type options) */
    case DATE = 'date';

    /** Username faker (supports length, prefix, suffix options) */
    case USERNAME = 'username';

    /** URL faker (supports scheme, domain, path options) */
    case URL = 'url';

    /** Company name faker (supports type, suffix options) */
    case COMPANY = 'company';

    /** Masking faker (supports partial masking with preserve options) */
    case MASKING = 'masking';

    /** Custom service faker (requires service name) */
    case SERVICE = 'service';
}
