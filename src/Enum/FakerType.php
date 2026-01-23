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

    /** Password faker (supports length, special chars, numbers, uppercase options) */
    case PASSWORD = 'password';

    /** IP address faker (supports IPv4/IPv6, public/private/localhost options) */
    case IP_ADDRESS = 'ip_address';

    /** MAC address faker (supports separator, uppercase options) */
    case MAC_ADDRESS = 'mac_address';

    /** UUID faker (supports version 1/4, format options) */
    case UUID = 'uuid';

    /** Hash faker (supports algorithm, length options) */
    case HASH = 'hash';

    /** Coordinate faker (supports format, precision, bounds options) */
    case COORDINATE = 'coordinate';

    /** Color faker (supports format, alpha options) */
    case COLOR = 'color';

    /** Boolean faker (supports true_probability option) */
    case BOOLEAN = 'boolean';

    /** Numeric faker (supports type, min, max, precision options) */
    case NUMERIC = 'numeric';

    /** File faker (supports extension, directory, absolute options) */
    case FILE = 'file';

    /** JSON faker (supports schema, depth, max_items options) */
    case JSON = 'json';

    /** Text faker (supports type, min_words, max_words options) */
    case TEXT = 'text';

    /** Enum faker (supports values, weighted options) */
    case ENUM = 'enum';

    /** Country faker (supports format, locale options) */
    case COUNTRY = 'country';

    /** Language faker (supports format, locale options) */
    case LANGUAGE = 'language';

    /** Hash preserve faker (deterministic anonymization with hash functions) */
    case HASH_PRESERVE = 'hash_preserve';

    /** Shuffle faker (shuffle values while maintaining distribution) */
    case SHUFFLE = 'shuffle';

    /** Constant faker (replace with constant value) */
    case CONSTANT = 'constant';

    /** Custom service faker (requires service name) */
    case SERVICE = 'service';

    /** Spanish DNI/CIF/NIF faker (supports type, formatted options) */
    case DNI_CIF = 'dni_cif';

    /** Name fallback faker (handles nullable related name fields) */
    case NAME_FALLBACK = 'name_fallback';

    /** HTML faker (generates HTML with lorem ipsum, perfect for email signatures) */
    case HTML = 'html';
}
