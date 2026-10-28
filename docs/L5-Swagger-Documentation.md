# L5-Swagger Documentation for NCC-ERP-AMS

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [API Documentation Setup](#api-documentation-setup)
5. [Writing API Documentation](#writing-api-documentation)
6. [Generating Swagger Documentation](#generating-swagger-documentation)
7. [Advanced Configuration](#advanced-configuration)

## Introduction

L5-Swagger is a Laravel package that integrates Swagger/OpenAPI documentation into Laravel applications. It automatically generates interactive API documentation from PHP annotations in your code.

### What is L5-Swagger?

- **L5-Swagger**: Laravel wrapper for swagger-php

- **Swagger/OpenAPI**: Specification for describing REST APIs
- **Interactive Documentation**: Auto-generated, testable API docs

### Benefits

- Interactive API documentation
- Built-in API testing interface
- Code-first documentation approach
- Automatic synchronization with code changes
- Support for authentication testing

## Installation

### 1. Install via Composer

```bash
composer require darkaonline/l5-swagger
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

This creates:

- `config/l5-swagger.php` - Main configuration file

- `resources/views/vendor/l5-swagger/` - Swagger UI views

### 3. Verify Installation

Access Swagger UI at: `http://127.0.0.1:8000/api/documentation`

## Configuration

The main configuration file is located at `config/l5-swagger.php`. Here's an explanation of key configuration sections:

### Basic API Information

```php
'api' => [
    'title' => env('L5_SWAGGER_API_TITLE', 'NCC-ERP-AMS API Documentation'),
    'version' => env('L5_SWAGGER_API_VERSION', '1.0.0'),
    'description' => env('L5_SWAGGER_API_DESCRIPTION', 'API Documentation for NCC-ERP-AMS System'),
    'contact' => [
        'name' => env('L5_SWAGGER_CONTACT_NAME', 'NCC Admin'),
        'email' => env('L5_SWAGGER_CONTACT_EMAIL', 'it@ncc.asia'),
        'url' => env('L5_SWAGGER_CONTACT_URL', 'https://ncc.asia')
    ],
]
```

### Routes Configuration

```php
'routes' => [
    'api' => env('L5_SWAGGER_ROUTE_API', 'api/documentation'),    // Swagger UI URL
    'docs' => env('L5_SWAGGER_ROUTE_DOCS', 'docs'),               // JSON docs URL
    'oauth2_callback' => 'api/oauth2-callback',                   // OAuth callback
]
```

### Paths for Annotations

```php
'annotations' => [
    base_path('app/Http/Controllers/Api'),     // API Controllers
    base_path('app/Http/Controllers/Assets'),   // Asset Controllers
    base_path('app/Http/Transformers'),        // Data Transformers
    base_path('app/Http/Requests'),            // Form Requests
    base_path('app/Swagger'),                  // Swagger Info Classes
],
```

### Security Configuration

```php
'securityDefinitions' => [
    'securitySchemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Enter Bearer token in format (Bearer <token>)'
        ],
    ],
    'security' => [
        [
            'bearerAuth' => [],
        ],
    ],
],
```

### Environment Variables

Add these to your `.env` file:

```env
# L5-Swagger Configuration
L5_SWAGGER_API_TITLE="NCC-ERP-AMS API Documentation"
L5_SWAGGER_API_VERSION="1.0.0"
L5_SWAGGER_API_DESCRIPTION="API Documentation for NCC-ERP-AMS System"
L5_SWAGGER_CONTACT_NAME="NCC Admin"
L5_SWAGGER_CONTACT_EMAIL="it@ncc.asia"
L5_SWAGGER_CONTACT_URL="https://ncc.asia"

L5_SWAGGER_ROUTE_API="api/documentation"
L5_SWAGGER_ROUTE_DOCS="docs"
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_GENERATE_YAML_COPY=false
L5_SWAGGER_USE_ABSOLUTE_PATH=true

# Swagger UI Configuration
L5_SWAGGER_UI_DARK_MODE=false
L5_SWAGGER_UI_DOC_EXPANSION="none"
L5_SWAGGER_UI_FILTERS=true
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false

# Server Configuration
L5_SWAGGER_CONST_HOST="http://localhost:8000"
L5_SWAGGER_CONST_API_BASE_URL="http://localhost:8000/api"
```

## API Documentation Setup

### 1. Create Swagger Info Class

The main API information is defined in `app/Swagger/ApiSwaggerInfo.php`:

```php
<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *   title="NCC-ERP-AMS API Documentation",
 *   version="1.0.0",
 *   description="API Documentation for NCC-ERP-AMS System",
 *   @OA\Contact(
 *     name="NCC Admin",
 *     email="it@ncc.asia",
 *     url="https://ncc.asia"
 *   ),
 *   @OA\License(
 *     name="MIT",
 *     url="https://opensource.org/licenses/MIT"
 *   )
 * )
 *
 * @OA\Server(
 *   url=L5_SWAGGER_CONST_HOST,
 *   description=L5_SWAGGER_SERVER_DESCRIPTION
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description=L5_SWAGGER_SECURITY_BEARER_DESCRIPTION
 * )
 */
class ApiSwaggerInfo {}
```

### 2. Define Global Components

Create `app/Swagger/Components.php` for reusable components:

```php
<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *   schema="Error",
 *   type="object",
 *   @OA\Property(property="status", type="string", example="error"),
 *   @OA\Property(property="message", type="string", example="Error message"),
 *   @OA\Property(property="payload", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="Success",
 *   type="object",
 *   @OA\Property(property="status", type="string", example="success"),
 *   @OA\Property(property="message", type="string", example="Operation successful"),
 *   @OA\Property(property="payload", type="object")
 * )
 *
 * @OA\Schema(
 *   schema="Pagination",
 *   type="object",
 *   @OA\Property(property="total", type="integer", example=100),
 *   @OA\Property(property="per_page", type="integer", example=20),
 *   @OA\Property(property="current_page", type="integer", example=1),
 *   @OA\Property(property="last_page", type="integer", example=5),
 *   @OA\Property(property="first_page_url", type="string"),
 *   @OA\Property(property="last_page_url", type="string"),
 *   @OA\Property(property="next_page_url", type="string", nullable=true),
 *   @OA\Property(property="prev_page_url", type="string", nullable=true),
 *   @OA\Property(property="path", type="string"),
 *   @OA\Property(property="from", type="integer", example=1),
 *   @OA\Property(property="to", type="integer", example=20)
 * )
 */
class Components {}
```

## Writing API Documentation

### 1. Controller Documentation

Example for `app/Http/Controllers/Api/AssetsController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// ... other imports

class AssetsController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/hardware",
     *   summary="Get list of assets",
     *   description="Retrieve a paginated list of hardware assets",
     *   operationId="getAssets",
     *   tags={"Assets"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Number of results per page",
     *     required=false,
     *     @OA\Schema(type="integer", minimum=1, maximum=500, default=50)
     *   ),
     *   @OA\Parameter(
     *     name="offset",
     *     in="query",
     *     description="Result offset for pagination",
     *     required=false,
     *     @OA\Schema(type="integer", minimum=0, default=0)
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search term for filtering assets",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Field to sort by",
     *     required=false,
     *     @OA\Schema(type="string", enum={"id", "name", "asset_tag", "serial", "model", "created_at"})
     *   ),
     *   @OA\Parameter(
     *     name="order",
     *     in="query",
     *     description="Sort order",
     *     required=false,
     *     @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="total", type="integer", example=250),
     *       @OA\Property(
     *         property="rows",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Asset")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(ref="#/components/schemas/Error")
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden",
     *     @OA\JsonContent(ref="#/components/schemas/Error")
     *   )
     * )
     */
    public function index(Request $request)
    {
        // Method implementation
    }

    /**
     * @OA\Post(
     *   path="/api/v1/hardware",
     *   summary="Create a new asset",
     *   description="Create a new hardware asset",
     *   operationId="storeAsset",
     *   tags={"Assets"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Asset data",
     *     @OA\JsonContent(ref="#/components/schemas/AssetRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Asset created successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Success")
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(ref="#/components/schemas/Error")
     *   )
     * )
     */
    public function store(AssetRequest $request)
    {
        // Method implementation
    }
}
```

### 2. Model Schemas

Create `app/Swagger/Schemas/AssetSchema.php`:

```php
<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *   schema="Asset",
 *   type="object",
 *   title="Asset",
 *   description="Hardware Asset model",
 *   required={"id", "name", "asset_tag"},
 *   @OA\Property(property="id", type="integer", format="int64", example=1),
 *   @OA\Property(property="name", type="string", maxLength=255, example="MacBook Pro"),
 *   @OA\Property(property="asset_tag", type="string", maxLength=255, example="NCC-LAPTOP-001"),
 *   @OA\Property(property="serial", type="string", maxLength=255, example="ABC123456789"),
 *   @OA\Property(property="model", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="MacBook Pro 13-inch")
 *   ),
 *   @OA\Property(property="status_label", type="object",
 *     @OA\Property(property="id", type="integer", example=2),
 *     @OA\Property(property="name", type="string", example="Ready to Deploy"),
 *     @OA\Property(property="status_type", type="string", example="deployable")
 *   ),
 *   @OA\Property(property="category", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Laptops")
 *   ),
 *   @OA\Property(property="manufacturer", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Apple")
 *   ),
 *   @OA\Property(property="supplier", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="TechSupplier Inc")
 *   ),
 *   @OA\Property(property="notes", type="string", example="Assigned to development team"),
 *   @OA\Property(property="location", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Hanoi Office")
 *   ),
 *   @OA\Property(property="rtd_location", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="IT Storage")
 *   ),
 *   @OA\Property(property="image", type="string", format="uri", example="https://app.com/uploads/assets/asset-image.jpg"),
 *   @OA\Property(property="assigned_to", type="object", nullable=true,
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="type", type="string", example="user")
 *   ),
 *   @OA\Property(property="warranty_months", type="integer", example=24),
 *   @OA\Property(property="warranty_expires", type="string", format="date", example="2025-12-31"),
 *   @OA\Property(property="created_at", type="string", format="datetime", example="2023-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="datetime", example="2023-06-20T14:45:00Z"),
 *   @OA\Property(property="purchase_date", type="string", format="date", example="2023-01-15"),
 *   @OA\Property(property="purchase_cost", type="string", example="1500.00"),
 *   @OA\Property(property="order_number", type="string", example="PO-2023-001"),
 *   @OA\Property(property="company", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="NCC Plus")
 *   ),
 *   @OA\Property(property="requestable", type="boolean", example=true),
 *   @OA\Property(property="user_can_checkout", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *   schema="AssetRequest",
 *   type="object",
 *   title="Asset Request",
 *   description="Asset creation/update request",
 *   required={"name", "model_id", "status_id"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="MacBook Pro"),
 *   @OA\Property(property="asset_tag", type="string", maxLength=255, example="NCC-LAPTOP-001"),
 *   @OA\Property(property="model_id", type="integer", example=1),
 *   @OA\Property(property="status_id", type="integer", example=2),
 *   @OA\Property(property="serial", type="string", maxLength=255, example="ABC123456789"),
 *   @OA\Property(property="purchase_date", type="string", format="date", example="2023-01-15"),
 *   @OA\Property(property="purchase_cost", type="number", format="float", example=1500.00),
 *   @OA\Property(property="order_number", type="string", example="PO-2023-001"),
 *   @OA\Property(property="notes", type="string", example="Development team laptop"),
 *   @OA\Property(property="warranty_months", type="integer", example=24),
 *   @OA\Property(property="supplier_id", type="integer", example=1),
 *   @OA\Property(property="company_id", type="integer", example=1),
 *   @OA\Property(property="location_id", type="integer", example=1),
 *   @OA\Property(property="rtd_location_id", type="integer", example=1),
 *   @OA\Property(property="requestable", type="boolean", example=true)
 * )
 */
class AssetSchema {}
```

### 3. Request Validation Documentation

Document form requests in `app/Http/Requests/AssetRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="AssetValidationError",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="The given data was invalid."),
 *   @OA\Property(property="errors", type="object",
 *     @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
 *     @OA\Property(property="model_id", type="array", @OA\Items(type="string", example="The model id field is required.")),
 *     @OA\Property(property="asset_tag", type="array", @OA\Items(type="string", example="The asset tag has already been taken."))
 *   )
 * )
 */
class AssetRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'model_id' => 'required|integer|exists:models,id',
            'status_id' => 'required|integer|exists:status_labels,id',
            'asset_tag' => 'nullable|string|unique:assets,asset_tag,' . $this->route('hardware'),
            // ... other rules
        ];
    }
}
```

## Generating Swagger Documentation

### 1. Manual Generation

Generate documentation manually:

```bash
# Generate JSON documentation
php artisan l5-swagger:generate

# Generate with specific configuration
php artisan l5-swagger:generate default

# Clear and regenerate
php artisan l5-swagger:publish --force
php artisan l5-swagger:generate
```

### 2. Automatic Generation

Enable automatic generation in development:

```php
// config/l5-swagger.php
'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true), // Set to true for development
```

### 3. Production Setup

For production, disable automatic generation:

```env
# .env
L5_SWAGGER_GENERATE_ALWAYS=false
```

Generate documentation during deployment:

```bash
# In your deployment script
php artisan l5-swagger:generate
```

### 4. Access Documentation

- **Swagger UI**: `http://127.0.0.1:8000/api/documentation`
- **JSON Schema**: `http://127.0.0.1:8000/docs/api-docs.json`
- **YAML Schema**: `http://127.0.0.1:8000/docs/api-docs.yaml` (if enabled)

## Advanced Configuration

### 1. Multiple API Versions

Configure multiple documentation sets:

```php
// config/l5-swagger.php
'documentations' => [
    'default' => [
        // v1 API configuration
        'api' => [
            'title' => 'NCC-ERP-AMS API v1',
        ],
        'paths' => [
            'annotations' => [
                base_path('app/Http/Controllers/Api/V1'),
            ],
        ],
    ],
    'v2' => [
        // v2 API configuration
        'api' => [
            'title' => 'NCC-ERP-AMS API v2',
        ],
        'paths' => [
            'annotations' => [
                base_path('app/Http/Controllers/Api/V2'),
            ],
        ],
    ],
],
```

### 2. Custom Security Schemes

Add API key authentication:

```php
'securityDefinitions' => [
    'securitySchemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
        'apiKey' => [
            'type' => 'apiKey',
            'name' => 'X-API-KEY',
            'in' => 'header',
        ],
    ],
],
```

### 3. Custom Processors

Create custom annotation processors:

```php
// app/Swagger/Processors/CustomProcessor.php
namespace App\Swagger\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Processors\ProcessorInterface;

class CustomProcessor implements ProcessorInterface
{
    public function __invoke(Analysis $analysis)
    {
        // Custom processing logic
    }
}
```

Register in configuration:

```php
'scanOptions' => [
    'processors' => [
        new \App\Swagger\Processors\CustomProcessor(),
    ],
],
```

### 4. Environment-Specific Configuration

Development configuration:

```env
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DARK_MODE=true
L5_SWAGGER_UI_DOC_EXPANSION="full"
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=true
```

Production configuration:

```env
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_UI_DARK_MODE=false
L5_SWAGGER_UI_DOC_EXPANSION="none"
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false
```
