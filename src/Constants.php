<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin;

/**
 * Constants for Plugin SDK
 * All values MUST come from constants - zero hardcoded values allowed
 */
final class Constants
{
    // HTTP Status Codes
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_CONFLICT = 409;
    public const HTTP_LOCKED = 423;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;

    // API Base Path
    public const API_BASE_PATH = '/api/v1';

    // Public License Endpoints
    public const API_ENDPOINT_LICENSES_ACTIVATE = '/api/v1/licenses/activate';
    public const API_ENDPOINT_LICENSES_VALIDATE = '/api/v1/licenses/validate';
    public const API_ENDPOINT_LICENSES_DEACTIVATE = '/api/v1/licenses/deactivate';
    public const API_ENDPOINT_LICENSES_GET = '/api/v1/licenses/%s';
    public const API_ENDPOINT_LICENSES_FEATURES = '/api/v1/licenses/%s/features';
    public const API_ENDPOINT_LICENSES_USAGE = '/api/v1/licenses/usage';

    // Update Endpoints
    public const API_ENDPOINT_UPDATES_CHECK = '/api/v1/updates/check';

    // License Status Values
    public const LICENSE_STATUS_ACTIVE = 'ACTIVE';
    public const LICENSE_STATUS_INACTIVE = 'INACTIVE';
    public const LICENSE_STATUS_EXPIRED = 'EXPIRED';
    public const LICENSE_STATUS_REVOKED = 'REVOKED';
    public const LICENSE_STATUS_SUSPENDED = 'SUSPENDED';

    // Error Codes
    public const ERROR_CODE_INVALID_FORMAT = 'INVALID_FORMAT';
    public const ERROR_CODE_INVALID_LICENSE_FORMAT = 'INVALID_LICENSE_FORMAT';
    public const ERROR_CODE_LICENSE_NOT_FOUND = 'LICENSE_NOT_FOUND';
    public const ERROR_CODE_LICENSE_INACTIVE = 'LICENSE_INACTIVE';
    public const ERROR_CODE_LICENSE_EXPIRED = 'LICENSE_EXPIRED';
    public const ERROR_CODE_ACTIVATION_LIMIT_EXCEEDED = 'ACTIVATION_LIMIT_EXCEEDED';
    public const ERROR_CODE_NOT_ACTIVATED_ON_DOMAIN = 'NOT_ACTIVATED_ON_DOMAIN';
    public const ERROR_CODE_DEMO_MODE_MISMATCH = 'DEMO_MODE_MISMATCH';
    public const ERROR_CODE_VALIDATION_ERROR = 'VALIDATION_ERROR';

    // HTTP Headers
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_ACCEPT = 'Accept';

    // Content Types
    public const CONTENT_TYPE_JSON = 'application/json';

    // Default Configuration Values
    public const DEFAULT_TIMEOUT_SECONDS = 15;
    public const DEFAULT_CACHE_TTL_SECONDS = 86400; // 24 hours
    public const DEFAULT_VALIDATION_CACHE_TTL_SECONDS = 3600; // 1 hour
    public const DEFAULT_FAILED_VALIDATION_CACHE_TTL_SECONDS = 3600; // 1 hour

    // Validation Limits
    public const VALIDATION_DOMAIN_MAX_LENGTH = 255;
    public const VALIDATION_SITE_NAME_MAX_LENGTH = 255;
    public const VALIDATION_VERSION_MAX_LENGTH = 50;
    public const VALIDATION_SLUG_MAX_LENGTH = 100;

    // License Key Pattern (Ed25519 format: payload.signature)
    public const LICENSE_KEY_PATTERN = '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/';
    public const LICENSE_KEY_LENGTH = 1000;

    // Response Keys
    public const RESPONSE_KEY_SUCCESS = 'success';
    public const RESPONSE_KEY_DATA = 'data';
    public const RESPONSE_KEY_ERROR = 'error';
    public const RESPONSE_KEY_CODE = 'code';
    public const RESPONSE_KEY_MESSAGE = 'message';
    public const RESPONSE_KEY_UPDATE = 'update';

    // WordPress Option Keys
    public const WP_OPTION_LICENSE_KEY = 'sls_license_key';
    public const WP_OPTION_LICENSE_STATUS = 'sls_license_status';
    public const WP_OPTION_LICENSE_EXPIRES_AT = 'sls_license_expires_at';
    public const WP_OPTION_LICENSE_FEATURES = 'sls_license_features';
    public const WP_OPTION_LICENSE_TIER_CODE = 'sls_license_tier_code';

    // WordPress Transient Keys
    public const WP_TRANSIENT_LICENSE_VALIDATION = 'sls_license_validation';
    public const WP_TRANSIENT_UPDATE_CHECK = 'sls_update_check';

    // WordPress Transient Values
    public const WP_TRANSIENT_VALUE_VALID = 'valid';
    public const WP_TRANSIENT_VALUE_INVALID = 'invalid';

    // WordPress Time Constants (in seconds)
    public const WP_TIME_DAY_IN_SECONDS = 86400;
    public const WP_TIME_HOUR_IN_SECONDS = 3600;
    public const WP_TIME_MINUTE_IN_SECONDS = 60;

    // Private constructor to prevent instantiation
    private function __construct()
    {
    }
}

