<?php

use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\LikertScaleOption;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\ReflectionChecklistItem;
use App\Models\ReflectionContent;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use App\Models\SimulationOrderingStep;
use App\Models\VideoContent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This value determines the classes that can be unserialized from cache
    | storage. By default, no PHP classes will be unserialized from your
    | cache to prevent gadget chain attacks if your APP_KEY is leaked.
    |
    | ContentTreeService::loadModuleTree() caches a fully-hydrated Module
    | (+ pages + polymorphic content + every nested relation, see
    | ContentTreeService::contentMorphMap()) as one object graph on the
    | "database" cache store. With this left at `false`, every class in that
    | graph unserializes back as __PHP_Incomplete_Class on a cache HIT (a
    | cache MISS never notices, since Cache::remember() returns the closure's
    | live result directly without a round-trip) — module detail then 500s
    | with "Return value must be of type Module, __PHP_Incomplete_Class
    | returned", but only ever on a *second* request for the same module.
    | The test suite never catches this: CACHE_STORE=array there, which
    | never serializes at all.
    |
    | Allowlisted below instead of turning this off entirely — every model
    | class (+ Eloquent's own Collection, which wraps every hasMany-style
    | relation) that can appear anywhere in that cached graph. Verified
    | exhaustively by serializing a module exercising all 5 content types
    | incl. every nested relation (including the easy-to-miss quiz likert
    | branch) and diffing every `O:<len>:"ClassName"` marker in the output.
    |
    | Adding a 6th ContentableType (or a new nested relation on an existing
    | one) needs its model class added here too, or module detail will
    | silently 500 for that content type specifically, in production only,
    | invisible to the test suite — exactly this bug, again.
    |
    */

    'serializable_classes' => [
        Module::class,
        Journey::class,
        ModulePage::class,
        VideoContent::class,
        ArticleContent::class,
        ArticleBlock::class,
        QuizContent::class,
        QuizSegment::class,
        QuizQuestion::class,
        QuizChoiceOption::class,
        LikertScaleOption::class,
        SimulationContent::class,
        SimulationMatchingPair::class,
        SimulationOrderingStep::class,
        ReflectionContent::class,
        ReflectionSection::class,
        ReflectionQuestion::class,
        ReflectionChecklistItem::class,
        Collection::class,
    ],

];
