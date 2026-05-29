<?php return array (
  4 => 'concurrency',
  'app' => 
  array (
    'name' => 'SEDyCO',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'America/Mexico_City',
    'locale' => 'es',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:6c4Pasyjpn13XT/fL+puD8LVgJdl+bAu2/i4K99soTg=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\AuthServiceProvider',
      25 => 'App\\Providers\\EventServiceProvider',
      26 => 'App\\Providers\\Filament\\AdminPanelProvider',
      27 => 'App\\Providers\\RouteServiceProvider',
      28 => 'Anhskohbo\\NoCaptcha\\NoCaptchaServiceProvider',
      29 => 'Nihir\\CountryStateCity\\CountryStateCityServiceProvider',
      30 => 'App\\Providers\\HelperServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Uri' => 'Illuminate\\Support\\Uri',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
      'NoCaptcha' => 'Anhskohbo\\NoCaptcha\\Facades\\NoCaptcha',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => NULL,
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'blade-icons' => 
  array (
    'sets' => 
    array (
    ),
    'class' => '',
    'attributes' => 
    array (
    ),
    'fallback' => '',
    'components' => 
    array (
      'disabled' => false,
      'default' => 'icon',
    ),
  ),
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'host' => 'api-mt1.pusher.com',
          'port' => '443',
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\framework/cache/data',
        'lock_path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '7ca6cb8f4be144206acbbd8d41717533',
        'secret' => '90d1f38634f1e5bd99f88d72e8d69b068f4deb73a9ed354db256261c3865aee3',
        'region' => 'auto',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'apc' => 
      array (
        'driver' => 'apc',
      ),
    ),
    'prefix' => 'sedyco_cache_',
  ),
  'captcha' => 
  array (
    'secret' => '6LdHfgUrAAAAAAf_yB-3DBYrzGF6Cr5wIoVXFGiG',
    'sitekey' => '6LdHfgUrAAAAAEinbVA4PM4cY4kAnp_HJE0Vy59p',
    'options' => 
    array (
      'timeout' => 30,
    ),
  ),
  'cleaver' => 
  array (
    'plantilla' => 
    array (
      'Persuasivo' => 
      array (
        'MOST' => 'I',
        'LEAST' => NULL,
      ),
      'Gentil' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Humilde' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Original' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'D',
      ),
      'Agresivo' => 
      array (
        'MOST' => 'D',
        'LEAST' => NULL,
      ),
      'Alma de la fiesta' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Comodino' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Temeroso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Agradable' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Temeroso de Dios' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Tenaz' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Atractivo' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Cauteloso' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Determinado' => 
      array (
        'MOST' => 'D',
        'LEAST' => NULL,
      ),
      'Convincente' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Bonachón' => 
      array (
        'MOST' => 'S',
        'LEAST' => NULL,
      ),
      'Dócil' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Atrevido' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Leal' => 
      array (
        'MOST' => 'S',
        'LEAST' => NULL,
      ),
      'Encantador' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Dispuesto' => 
      array (
        'MOST' => 'S',
        'LEAST' => NULL,
      ),
      'Deseoso' => 
      array (
        'MOST' => NULL,
        'LEAST' => NULL,
      ),
      'Consecuente' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Entusiasta' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'D',
      ),
      'Fuerza de voluntad' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'D',
      ),
      'Mente abierta' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Complaciente' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Animoso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'I',
      ),
      'Confiado' => 
      array (
        'MOST' => 'I',
        'LEAST' => NULL,
      ),
      'Simpatizador' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Tolerante' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Afirmativo' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Ecuánime' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Preciso' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Nervioso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'D',
      ),
      'Jovial' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'I',
      ),
      'Disciplinado' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Generoso' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Persistente' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Competitivo' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Alegre' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'I',
      ),
      'Considerado' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Armonioso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Admirable' => 
      array (
        'MOST' => 'I',
        'LEAST' => NULL,
      ),
      'Bondadoso' => 
      array (
        'MOST' => 'S',
        'LEAST' => NULL,
      ),
      'Resignado' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Carácter Firme' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Obediente' => 
      array (
        'MOST' => 'S',
        'LEAST' => NULL,
      ),
      'Quisquilloso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Inconquistable' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Juguetón' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Respetuoso' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Emprendedor' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Optimista' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Servicial' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Valiente' => 
      array (
        'MOST' => 'D',
        'LEAST' => NULL,
      ),
      'Inspirador' => 
      array (
        'MOST' => 'I',
        'LEAST' => NULL,
      ),
      'Sumiso' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Tímido' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Adaptable' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Disputador' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Indiferente' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Sangre liviana' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Amiguero' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Paciente' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Confianza en sí mismo' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Mesurado para hablar' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Conforme' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Confiable' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'I',
      ),
      'Pacífico' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
      'Positivo' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Aventurero' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Receptivo' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Cordial' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'I',
      ),
      'Moderado' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Indulgente' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Esteta' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Vigoroso' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Sociable' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Parlanchín' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Controlado' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Convencional' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'C',
      ),
      'Decisivo' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Cohibido' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'S',
      ),
      'Exacto' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Franco' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Buen compañero' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Diplomático' => 
      array (
        'MOST' => 'C',
        'LEAST' => NULL,
      ),
      'Audaz' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Refinado' => 
      array (
        'MOST' => NULL,
        'LEAST' => 'I',
      ),
      'Satisfecho' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Inquieto' => 
      array (
        'MOST' => 'D',
        'LEAST' => 'D',
      ),
      'Popular' => 
      array (
        'MOST' => 'I',
        'LEAST' => 'I',
      ),
      'Buen vecino' => 
      array (
        'MOST' => 'S',
        'LEAST' => 'S',
      ),
      'Devoto' => 
      array (
        'MOST' => 'C',
        'LEAST' => 'C',
      ),
    ),
    'percentiles' => 
    array (
      'M' => 
      array (
        'D' => 
        array (
          0 => 1,
          1 => 5,
          2 => 10,
          3 => 20,
          4 => 30,
          5 => 40,
          6 => 50,
          7 => 60,
          8 => 65,
          9 => 75,
          10 => 84,
          11 => 87,
          12 => 90,
          13 => 93,
          14 => 95,
          15 => 97,
          16 => 97,
          17 => 98,
          18 => 98,
          19 => 98,
          20 => 99,
        ),
        'I' => 
        array (
          0 => 4,
          1 => 10,
          2 => 25,
          3 => 40,
          4 => 55,
          5 => 70,
          6 => 82,
          7 => 90,
          8 => 95,
          9 => 96,
          10 => 97,
          11 => 97,
          12 => 97,
          13 => 97,
          14 => 97,
          15 => 97,
          16 => 97,
          17 => 99,
        ),
        'S' => 
        array (
          0 => 5,
          1 => 10,
          2 => 16,
          3 => 30,
          4 => 40,
          5 => 55,
          6 => 63,
          7 => 75,
          8 => 84,
          9 => 90,
          10 => 95,
          11 => 96,
          12 => 97,
          13 => 97,
          14 => 97,
          15 => 97,
          16 => 98,
          17 => 98,
          18 => 98,
          19 => 99,
        ),
        'C' => 
        array (
          0 => 1,
          1 => 5,
          2 => 16,
          3 => 30,
          4 => 55,
          5 => 70,
          6 => 84,
          7 => 93,
          8 => 95,
          9 => 97,
          10 => 97,
          11 => 97,
          12 => 98,
          13 => 98,
          14 => 98,
          15 => 99,
        ),
      ),
      'L' => 
      array (
        'D' => 
        array (
          0 => 99,
          1 => 95,
          2 => 87,
          3 => 80,
          4 => 65,
          5 => 55,
          6 => 50,
          7 => 35,
          8 => 30,
          9 => 20,
          10 => 18,
          11 => 15,
          12 => 10,
          13 => 6,
          14 => 5,
          15 => 4,
          16 => 3,
          17 => 2,
          18 => 2,
          19 => 2,
          20 => 2,
          21 => 1,
        ),
        'I' => 
        array (
          0 => 99,
          1 => 95,
          2 => 87,
          3 => 75,
          4 => 55,
          5 => 40,
          6 => 25,
          7 => 16,
          8 => 10,
          9 => 5,
          10 => 4,
          11 => 4,
          12 => 3,
          13 => 3,
          14 => 3,
          15 => 2,
          16 => 2,
          17 => 2,
          18 => 2,
          19 => 1,
        ),
        'S' => 
        array (
          0 => 99,
          1 => 97,
          2 => 95,
          3 => 87,
          4 => 80,
          5 => 65,
          6 => 55,
          7 => 35,
          8 => 28,
          9 => 18,
          10 => 10,
          11 => 5,
          12 => 4,
          13 => 3,
          14 => 3,
          15 => 3,
          16 => 2,
          17 => 2,
          18 => 2,
          19 => 1,
        ),
        'C' => 
        array (
          0 => 99,
          1 => 97,
          2 => 95,
          3 => 90,
          4 => 84,
          5 => 70,
          6 => 55,
          7 => 40,
          8 => 38,
          9 => 23,
          10 => 10,
          11 => 5,
          12 => 4,
          13 => 3,
          14 => 2,
          15 => 2,
          16 => 1,
        ),
      ),
      'T' => 
      array (
        'D' => 
        array (
          -21 => 1,
          -20 => 2,
          -19 => 2,
          -18 => 2,
          -17 => 2,
          -16 => 2,
          -15 => 2,
          -14 => 2,
          -13 => 4,
          -12 => 5,
          -11 => 5,
          -10 => 9,
          -9 => 13,
          -8 => 15,
          -7 => 16,
          -6 => 20,
          -5 => 25,
          -4 => 29,
          -3 => 35,
          -2 => 40,
          -1 => 45,
          0 => 50,
          1 => 55,
          2 => 60,
          3 => 65,
          4 => 67,
          5 => 70,
          6 => 75,
          7 => 80,
          8 => 84,
          9 => 85,
          10 => 90,
          11 => 91,
          12 => 94,
          13 => 95,
          14 => 96,
          15 => 97,
          16 => 97,
          17 => 98,
          18 => 98,
          19 => 98,
          20 => 99,
        ),
        'I' => 
        array (
          -19 => 1,
          -18 => 2,
          -17 => 2,
          -16 => 2,
          -15 => 2,
          -14 => 2,
          -13 => 2,
          -12 => 2,
          -11 => 2,
          -10 => 3,
          -9 => 4,
          -8 => 5,
          -7 => 6,
          -6 => 10,
          -5 => 16,
          -4 => 20,
          -3 => 29,
          -2 => 35,
          -1 => 45,
          0 => 55,
          1 => 60,
          2 => 70,
          3 => 75,
          4 => 85,
          5 => 90,
          6 => 95,
          7 => 96,
          8 => 97,
          9 => 97,
          10 => 98,
          11 => 98,
          12 => 98,
          13 => 98,
          14 => 98,
          15 => 98,
          16 => 98,
          17 => 99,
        ),
        'S' => 
        array (
          -19 => 1,
          -18 => 2,
          -17 => 2,
          -16 => 2,
          -15 => 2,
          -14 => 2,
          -13 => 2,
          -12 => 3,
          -11 => 4,
          -10 => 5,
          -9 => 8,
          -8 => 10,
          -7 => 15,
          -6 => 20,
          -5 => 25,
          -4 => 30,
          -3 => 35,
          -2 => 40,
          -1 => 50,
          0 => 57,
          1 => 60,
          2 => 70,
          3 => 75,
          4 => 80,
          5 => 84,
          6 => 87,
          7 => 91,
          8 => 94,
          9 => 96,
          10 => 97,
          11 => 97,
          12 => 98,
          13 => 98,
          14 => 98,
          15 => 98,
          16 => 98,
          17 => 98,
          18 => 98,
          19 => 99,
        ),
        'C' => 
        array (
          -16 => 1,
          -15 => 2,
          -14 => 2,
          -13 => 2,
          -12 => 2,
          -11 => 3,
          -10 => 4,
          -9 => 6,
          -8 => 9,
          -7 => 13,
          -6 => 20,
          -5 => 25,
          -4 => 35,
          -3 => 40,
          -2 => 55,
          -1 => 60,
          0 => 70,
          1 => 75,
          2 => 84,
          3 => 90,
          4 => 95,
          5 => 96,
          6 => 97,
          7 => 97,
          8 => 98,
          9 => 98,
          10 => 98,
          11 => 98,
          12 => 98,
          13 => 98,
          14 => 98,
          15 => 99,
        ),
      ),
    ),
    'interpretations' => 
    array (
      'D' => 
      array (
        'name' => 'Dominancia o Empuje',
        'description' => 'Capacidad de liderazgo, de lograr resultados y aceptar retos.',
        'high' => 
        array (
          'title' => 'Alto (D+)',
          'traits' => 'Orientado a resultados, toma decisiones rápidas, asume riesgos, competitivo, directo.',
          'behavior' => 'Persona enfocada en resolver problemas y superar obstáculos. Prefiere tomar el control y actuar con autoridad. Excelente para arrancar proyectos o manejar crisis.',
        ),
        'low' => 
        array (
          'title' => 'Bajo (D-)',
          'traits' => 'Pacífico, conservador, analítico ante riesgos, evita conflictos, modesto.',
          'behavior' => 'Prefiere un entorno predecible. Investiga y recopila datos antes de actuar. Evita la confrontación directa y busca el consenso antes que la imposición.',
        ),
      ),
      'I' => 
      array (
        'name' => 'Influencia o Relación',
        'description' => 'Habilidad para relacionarse con la gente y motivarla.',
        'high' => 
        array (
          'title' => 'Alto (I+)',
          'traits' => 'Sociable, persuasivo, entusiasta, optimista, comunicativo.',
          'behavior' => 'Logra resultados a través de la persuasión y la motivación de otros. Destaca en relaciones públicas, ventas y negociaciones. Confía en su instinto social.',
        ),
        'low' => 
        array (
          'title' => 'Bajo (I-)',
          'traits' => 'Lógico, reservado, calculador, prefiere datos a emociones.',
          'behavior' => 'Se enfoca en los hechos y la lógica por encima de los sentimientos. Es más distante en sus relaciones laborales. Prefiere trabajar solo o con equipos muy técnicos.',
        ),
      ),
      'S' => 
      array (
        'name' => 'Constancia o Permanencia',
        'description' => 'Capacidad para realizar trabajos de manera continua y rutinaria.',
        'high' => 
        array (
          'title' => 'Alto (S+)',
          'traits' => 'Paciente, predecible, buen oyente, consistente, enfocado en rutinas.',
          'behavior' => 'Muestra gran resistencia para tareas de largo plazo o repetitivas. Excelente jugador de equipo que busca estabilidad, lealtad y armonía a largo plazo.',
        ),
        'low' => 
        array (
          'title' => 'Bajo (S-)',
          'traits' => 'Flexible, impaciente, adaptable, prefiere la variedad.',
          'behavior' => 'Bajo nivel de tolerancia a la rutina. Busca variedad, movilidad y cambio constante. Reacciona rápido ante emergencias pero se aburre en tareas mecánicas.',
        ),
      ),
      'C' => 
      array (
        'name' => 'Apego o Cumplimiento',
        'description' => 'Habilidad para desarrollar trabajos respetando normas y procedimientos.',
        'high' => 
        array (
          'title' => 'Alto (C+)',
          'traits' => 'Preciso, perfeccionista, apegado a reglas, analítico, detallista.',
          'behavior' => 'Exige alta calidad y perfección. Sigue manuales y procedimientos al pie de la letra para evitar riesgos y errores. Trabaja mejor en entornos altamente estructurados.',
        ),
        'low' => 
        array (
          'title' => 'Bajo (C-)',
          'traits' => 'Independiente, firme, no convencional, delega detalles.',
          'behavior' => 'No le gusta ser micro-gestionado. Prefiere operar con independencia y autonomía. Ve las reglas como guías flexibles más que como leyes inquebrantables.',
        ),
      ),
      'situational' => 
      array (
        'D' => 
        array (
          'M' => 
          array (
            'high' => 'Se siente fuertemente motivado por el poder, la autoridad y los retos. Busca proyectar una imagen de líder enfocado en ganar y superar obstáculos.',
            'low' => 'Su motivación principal es la paz y la armonía. Proyecta un perfil pacífico, buscando entornos donde no tenga que imponerse ni confrontar a otros.',
          ),
          'L' => 
          array (
            'high' => 'Bajo fuerte estrés o crisis, tiende a volverse autoritario, exigente y directo hasta la rudeza, tomando el control de forma agresiva.',
            'low' => 'Ante problemas graves o confrontaciones directas, tiende a ceder el control, evitar decisiones impopulares y huir del conflicto.',
          ),
        ),
        'I' => 
        array (
          'M' => 
          array (
            'high' => 'Su mayor motivación es el reconocimiento público, la popularidad y el contacto social. Busca proyectar una imagen persuasiva y carismática.',
            'low' => 'Se motiva en entornos lógicos y de trabajo individual. Proyecta un perfil que prefiere enfocarse en los datos y tareas antes que en agradar a los demás.',
          ),
          'L' => 
          array (
            'high' => 'Bajo estrés, tiende a hablar de más, prometer cosas difíciles de cumplir o volverse excesivamente emocional para evadir la presión.',
            'low' => 'En situaciones de crisis se vuelve distante, frío y calculador. Pierde su expresividad y prefiere aislarse del contacto social para pensar.',
          ),
        ),
        'S' => 
        array (
          'M' => 
          array (
            'high' => 'Le motiva la seguridad, la estabilidad a largo plazo y un ritmo predecible. Proyecta una imagen leal, buscando ambientes familiares y de apoyo mutuo.',
            'low' => 'Se siente motivado por la variedad, el dinamismo y los cambios rápidos. Anhela la movilidad y rechaza las rutinas estancadas.',
          ),
          'L' => 
          array (
            'high' => 'Ante la presión se aferra al status quo, resiste obstinadamente los cambios repentinos y puede mostrarse terco o pasivo-agresivo.',
            'low' => 'Bajo estrés se vuelve errático e impaciente. Puede abandonar procedimientos, perder la concentración y saltar de una tarea a otra sin terminar.',
          ),
        ),
        'C' => 
        array (
          'M' => 
          array (
            'high' => 'Se motiva en entornos altamente estructurados, con reglas precisas y cero margen de error. Proyecta una imagen de máxima exactitud y perfeccionismo.',
            'low' => 'Anhela la independencia y la libertad operativa. Su motivación es trabajar sin ataduras a manuales estrictos, delegando los detalles técnicos.',
          ),
          'L' => 
          array (
            'high' => 'Bajo presión sufre de "parálisis por análisis". Se vuelve sumamente crítico, teme equivocarse y se escuda rígidamente en las reglas y el manual.',
            'low' => 'Ante emergencias o crisis operativa, ignora los procedimientos, toma atajos riesgosos y se vuelve muy descuidado con los detalles importantes.',
          ),
        ),
        'T' => 
        array (
          'general' => 'Esta puntuación refleja su comportamiento natural y diario en condiciones normales de trabajo.',
        ),
      ),
      'alerts' => 
      array (
        'flattened_profile' => 'Perfil Aplanado: Las puntuaciones se concentran entre el percentil 40 y 60. Esto puede indicar transición de puesto, confusión del candidato al responder, o neutralización de factores por alto estrés.',
      ),
    ),
    'glosario' => 
    array (
      1 => 
      array (
        0 => 
        array (
          'frase' => 'Persuasivo',
          'definicion' => 'capaz de llevar a otro a creer o hacer algo',
        ),
        1 => 
        array (
          'frase' => 'Gentil',
          'definicion' => 'aquel que en su trato es amable',
        ),
        2 => 
        array (
          'frase' => 'Humilde',
          'definicion' => 'que demuestra sencillez y docilidad',
        ),
        3 => 
        array (
          'frase' => 'Original',
          'definicion' => 'especial, único, distinto a los demás',
        ),
      ),
      2 => 
      array (
        0 => 
        array (
          'frase' => 'Agresivo',
          'definicion' => 'aquel que ataca u ofende a los demás',
        ),
        1 => 
        array (
          'frase' => 'Alma de las fiestas',
          'definicion' => 'el que es alegre, entusiasta',
        ),
        2 => 
        array (
          'frase' => 'Comodino',
          'definicion' => 'el que busca lo agradable con el menor esfuerzo',
        ),
        3 => 
        array (
          'frase' => 'Temeroso',
          'definicion' => 'aquel que es miedoso, cobarde',
        ),
      ),
      3 => 
      array (
        0 => 
        array (
          'frase' => 'Agradable',
          'definicion' => 'aquel que es grato y atrayente',
        ),
        1 => 
        array (
          'frase' => 'Temeroso de Dios',
          'definicion' => 'que tiene miedo al castigo divino',
        ),
        2 => 
        array (
          'frase' => 'Tenaz',
          'definicion' => 'aquel que es constante para alcanzar sus objetivos',
        ),
        3 => 
        array (
          'frase' => 'Atractivo',
          'definicion' => 'aquel que con sus cualidades capta la atención',
        ),
      ),
      4 => 
      array (
        0 => 
        array (
          'frase' => 'Cauteloso',
          'definicion' => 'precavido, que actúa previendo posibles dificultades',
        ),
        1 => 
        array (
          'frase' => 'Determinado',
          'definicion' => 'resuelto y decidido',
        ),
        2 => 
        array (
          'frase' => 'Convincente',
          'definicion' => 'que consigue que una idea o hecho se acepten como verdad',
        ),
        3 => 
        array (
          'frase' => 'Bonachón',
          'definicion' => 'aquel que es accesible, cordial y/o crédulo',
        ),
      ),
      5 => 
      array (
        0 => 
        array (
          'frase' => 'Dócil',
          'definicion' => 'aquel que fácilmente se amolda a los requerimientos',
        ),
        1 => 
        array (
          'frase' => 'Atrevido',
          'definicion' => 'que actúa de manera arriesgada, con decisión y arrojo',
        ),
        2 => 
        array (
          'frase' => 'Leal',
          'definicion' => 'que es fiel a personas o ideales',
        ),
        3 => 
        array (
          'frase' => 'Encantador',
          'definicion' => 'que cautiva la atención, seductor',
        ),
      ),
      6 => 
      array (
        0 => 
        array (
          'frase' => 'Dispuesto',
          'definicion' => 'disponible, preparado y con ánimo favorable para participar',
        ),
        1 => 
        array (
          'frase' => 'Deseoso',
          'definicion' => 'que anhela poseer, o realizar una cierta actividad',
        ),
        2 => 
        array (
          'frase' => 'Consecuente',
          'definicion' => 'aquel con quien es fácil llegar a un acuerdo',
        ),
        3 => 
        array (
          'frase' => 'Entusiasta',
          'definicion' => 'el que se siente motivado',
        ),
      ),
      7 => 
      array (
        0 => 
        array (
          'frase' => 'Fuerza de voluntad',
          'definicion' => 'con convencimiento interno y tenacidad para alcanzar un objetivo',
        ),
        1 => 
        array (
          'frase' => 'Mente abierta',
          'definicion' => 'que es tolerante y acepta ideas nuevas y distintas a las suyas',
        ),
        2 => 
        array (
          'frase' => 'Complaciente',
          'definicion' => 'el que busca ser agradable y dar satisfacción a otros',
        ),
        3 => 
        array (
          'frase' => 'Animoso',
          'definicion' => 'que muestra energía, motivación y deseos para hacer algo',
        ),
      ),
      8 => 
      array (
        0 => 
        array (
          'frase' => 'Confiado',
          'definicion' => 'satisfecho de si mismo, crédulo, ingenuo',
        ),
        1 => 
        array (
          'frase' => 'Simpatizador',
          'definicion' => 'el que trata de mantener relaciones agradables con los demás',
        ),
        2 => 
        array (
          'frase' => 'Tolerante',
          'definicion' => 'que respeta y sobrelleva aquello que no le es familiar',
        ),
        3 => 
        array (
          'frase' => 'Afirmativo',
          'definicion' => 'que sostiene sus ideas o actitudes con determinación',
        ),
      ),
      9 => 
      array (
        0 => 
        array (
          'frase' => 'Ecuánime',
          'definicion' => 'aquella persona que se muestra equilibrada y serena',
        ),
        1 => 
        array (
          'frase' => 'Preciso',
          'definicion' => 'que es exacto y conciso',
        ),
        2 => 
        array (
          'frase' => 'Nervioso',
          'definicion' => 'que es irritable, inquieto, impresionable',
        ),
        3 => 
        array (
          'frase' => 'Jovial',
          'definicion' => 'alegre, festivo, divertido',
        ),
      ),
      10 => 
      array (
        0 => 
        array (
          'frase' => 'Disciplinado',
          'definicion' => 'que sigue un método de manera continua',
        ),
        1 => 
        array (
          'frase' => 'Generoso',
          'definicion' => 'que da lo que tiene',
        ),
        2 => 
        array (
          'frase' => 'Animoso',
          'definicion' => 'que muestra energía, motivación y deseos de hacer algo',
        ),
        3 => 
        array (
          'frase' => 'Persistente',
          'definicion' => 'que es constante y tenaz en una actividad que se propone',
        ),
      ),
      11 => 
      array (
        0 => 
        array (
          'frase' => 'Competitivo',
          'definicion' => 'que tiene la disposición y características para rivalizar',
        ),
        1 => 
        array (
          'frase' => 'Alegre',
          'definicion' => 'que se muestra contento y regocijado. Gozoso',
        ),
        2 => 
        array (
          'frase' => 'Considerado',
          'definicion' => 'que es atento y toma en cuenta a los demás',
        ),
        3 => 
        array (
          'frase' => 'Armonioso',
          'definicion' => 'que busca el acuerdo, la convivencia, lo equilibrado',
        ),
      ),
      12 => 
      array (
        0 => 
        array (
          'frase' => 'Admirable',
          'definicion' => 'notable, digno de respeto y aprecio',
        ),
        1 => 
        array (
          'frase' => 'Bondadoso',
          'definicion' => 'que se inclina hacer el bien, amable',
        ),
        2 => 
        array (
          'frase' => 'Resignado',
          'definicion' => 'que se conforma con la situación en la que vive',
        ),
        3 => 
        array (
          'frase' => 'Carácter firme',
          'definicion' => 'que es comprometido con sus convicciones',
        ),
      ),
      13 => 
      array (
        0 => 
        array (
          'frase' => 'Obediente',
          'definicion' => 'que se apega a lo establecido',
        ),
        1 => 
        array (
          'frase' => 'Quisquilloso',
          'definicion' => 'que toma en cuenta hasta el más mínimo detalle, susceptible',
        ),
        2 => 
        array (
          'frase' => 'Inconquistable',
          'definicion' => 'alguien de quien es muy difícil obtener el aprecio o aceptación',
        ),
        3 => 
        array (
          'frase' => 'Juguetón',
          'definicion' => 'travieso, inquieto',
        ),
      ),
      14 => 
      array (
        0 => 
        array (
          'frase' => 'Respetuoso',
          'definicion' => 'que toma en cuenta y acata la reglas',
        ),
        1 => 
        array (
          'frase' => 'Emprendedor',
          'definicion' => 'que propone e inicia proyectos',
        ),
        2 => 
        array (
          'frase' => 'Optimista',
          'definicion' => 'que ve las cosas de manera favorable',
        ),
        3 => 
        array (
          'frase' => 'Servicial',
          'definicion' => 'dispuesto a complacer y ayudar',
        ),
      ),
      15 => 
      array (
        0 => 
        array (
          'frase' => 'Valiente',
          'definicion' => 'que enfrenta el peligro',
        ),
        1 => 
        array (
          'frase' => 'Inspirador',
          'definicion' => 'que hace surgir ideas creativas',
        ),
        2 => 
        array (
          'frase' => 'Sumiso',
          'definicion' => 'que es manejable, dócil, obediente',
        ),
        3 => 
        array (
          'frase' => 'Tímido',
          'definicion' => 'que se muestra poco abierto con las personas',
        ),
      ),
      16 => 
      array (
        0 => 
        array (
          'frase' => 'Adaptable',
          'definicion' => 'que se acomoda o ajusta a circunstancias y condiciones',
        ),
        1 => 
        array (
          'frase' => 'Disputador',
          'definicion' => 'el que discute sus razonamientos para demostrar algo',
        ),
        2 => 
        array (
          'frase' => 'Indiferente',
          'definicion' => 'el que no tenga preferencia o interés por algo',
        ),
        3 => 
        array (
          'frase' => 'Sangre liviana',
          'definicion' => 'que en su manera de ser es agradable y atractivo, con carisma',
        ),
      ),
      17 => 
      array (
        0 => 
        array (
          'frase' => 'Amiguero',
          'definicion' => 'que gusta de establecer relaciones afectuosas',
        ),
        1 => 
        array (
          'frase' => 'Paciente',
          'definicion' => 'que tiene la capacidad para esperar con tranquilidad',
        ),
        2 => 
        array (
          'frase' => 'Confianza en si mismo',
          'definicion' => 'que se siente seguro y satisfecho con su manera de ser y actuar.',
        ),
        3 => 
        array (
          'frase' => 'Mesurado por hablar',
          'definicion' => 'que evita los excesos al expresarse verbalmente',
        ),
      ),
      18 => 
      array (
        0 => 
        array (
          'frase' => 'Conforme',
          'definicion' => 'que se resigna a las circunstancias',
        ),
        1 => 
        array (
          'frase' => 'Confiable',
          'definicion' => 'que inspira un sentimiento de seguridad',
        ),
        2 => 
        array (
          'frase' => 'Pacifico',
          'definicion' => 'tranquilo, que está en paz, que evita peleas',
        ),
        3 => 
        array (
          'frase' => 'Positivo',
          'definicion' => 'que muestra lo que es cierto, real autentico.',
        ),
      ),
      19 => 
      array (
        0 => 
        array (
          'frase' => 'Aventurero',
          'definicion' => 'que gusta de tener experiencias novedosas y arriesgadas',
        ),
        1 => 
        array (
          'frase' => 'Receptivo',
          'definicion' => 'que percibe fácilmente sentimientos, ideas y hechos',
        ),
        2 => 
        array (
          'frase' => 'Cordial',
          'definicion' => 'que es respetuoso y amable con su trato',
        ),
        3 => 
        array (
          'frase' => 'Moderado',
          'definicion' => 'el que en su conducta se mantiene alejado de los extremos',
        ),
      ),
      20 => 
      array (
        0 => 
        array (
          'frase' => 'Indulgente',
          'definicion' => 'que es tolerante respecto a errores, que perdona fácilmente',
        ),
        1 => 
        array (
          'frase' => 'Esteta',
          'definicion' => 'el que gusta de lo bello',
        ),
        2 => 
        array (
          'frase' => 'Vigoroso',
          'definicion' => 'que tiene energía, fuerza y vitalidad',
        ),
        3 => 
        array (
          'frase' => 'Sociable',
          'definicion' => 'que busca la convivencia con sus semejantes',
        ),
      ),
      21 => 
      array (
        0 => 
        array (
          'frase' => 'Parlanchín',
          'definicion' => 'que habla mucho',
        ),
        1 => 
        array (
          'frase' => 'Controlado',
          'definicion' => 'que se domina y frena a si mismo',
        ),
        2 => 
        array (
          'frase' => 'Convencional',
          'definicion' => 'apegado a las costumbres, evita cambios e innovaciones',
        ),
        3 => 
        array (
          'frase' => 'Decisivo',
          'definicion' => 'que es tajante, terminante y definitivo',
        ),
      ),
      22 => 
      array (
        0 => 
        array (
          'frase' => 'Cohibido',
          'definicion' => 'que se siente restringido, intimidado para actuar, avergonzado',
        ),
        1 => 
        array (
          'frase' => 'Exacto',
          'definicion' => 'el que cuida los más pequeños detalles, conciso',
        ),
        2 => 
        array (
          'frase' => 'Franco',
          'definicion' => 'el que dice lo que piensa abiertamente, comunicativo',
        ),
        3 => 
        array (
          'frase' => 'Buen compañero',
          'definicion' => 'atento, cortés, respetuoso, cooperativo',
        ),
      ),
      23 => 
      array (
        0 => 
        array (
          'frase' => 'Diplomático',
          'definicion' => 'que tiene tacto y delicadeza en su trato',
        ),
        1 => 
        array (
          'frase' => 'Audaz',
          'definicion' => 'que se atreve a correr riesgos',
        ),
        2 => 
        array (
          'frase' => 'Refinado',
          'definicion' => 'distinguido, muy fino y delicado',
        ),
        3 => 
        array (
          'frase' => 'Satisfecho',
          'definicion' => 'que se siente complacido con lo que ha logrado',
        ),
      ),
      24 => 
      array (
        0 => 
        array (
          'frase' => 'Inquieto',
          'definicion' => 'que está en constante actividad',
        ),
        1 => 
        array (
          'frase' => 'Popular',
          'definicion' => 'que es aceptado y querido en un grupo',
        ),
        2 => 
        array (
          'frase' => 'Buen vecino',
          'definicion' => 'respetuoso, amable, considerado',
        ),
        3 => 
        array (
          'frase' => 'Devoto',
          'definicion' => 'que vive de acuerdo a costumbres e ideas religiosas',
        ),
      ),
    ),
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'sedyco_optradb',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'sedyco_optradb',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'sedyco_optradb',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'sedyco_optradb',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'sedyco_optradb',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 'migrations',
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'sedyco_database_',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
    ),
  ),
  'deepseek' => 
  array (
    'api_key' => 'sk-e55d2bb6229241dc8f51943e0f25801c',
    'base_url' => 'https://api.deepseek.com/v3',
    'timeout' => 30,
  ),
  'excel-import' => 
  array (
    'upload_disk' => 'sedyco_disk',
    'disk' => 'sedyco_disk',
  ),
  'filament' => 
  array (
    'broadcasting' => 
    array (
    ),
    'default_filesystem_disk' => 'public',
    'assets_path' => NULL,
    'cache_path' => 'C:\\xampp\\htdocs\\sedyco\\bootstrap/cache/filament',
    'livewire_loading_delay' => 'default',
    'system_route_prefix' => 'filament',
    'sidebar' => 
    array (
    ),
  ),
  'filament-spatie-roles-permissions' => 
  array (
    'resources' => 
    array (
      'PermissionResource' => 'Althinect\\FilamentSpatieRolesPermissions\\Resources\\PermissionResource',
      'RoleResource' => 'Althinect\\FilamentSpatieRolesPermissions\\Resources\\RoleResource',
    ),
    'preload_roles' => true,
    'preload_permissions' => true,
    'navigation_section_group' => 'filament-spatie-roles-permissions::filament-spatie.section.roles_and_permissions',
    'team_model' => 'App\\Models\\Team',
    'scope_to_tenant' => true,
    'scope_roles_to_tenant' => true,
    'scope_premissions_to_tenant' => false,
    'super_admin_role_name' => 'Super Admin',
    'should_register_on_navigation' => 
    array (
      'permissions' => false,
      'roles' => false,
    ),
    'should_show_permissions_for_roles' => true,
    'should_use_simple_modal_resource' => 
    array (
      'permissions' => false,
      'roles' => false,
    ),
    'should_remove_empty_state_actions' => 
    array (
      'permissions' => false,
      'roles' => false,
    ),
    'should_redirect_to_index' => 
    array (
      'permissions' => 
      array (
        'after_create' => false,
        'after_edit' => false,
      ),
      'roles' => 
      array (
        'after_create' => false,
        'after_edit' => false,
      ),
    ),
    'should_display_relation_managers' => 
    array (
      'permissions' => true,
      'users' => true,
      'roles' => true,
    ),
    'clusters' => 
    array (
      'permissions' => NULL,
      'roles' => NULL,
    ),
    'guard_names' => 
    array (
      'web' => 'web',
      'api' => 'api',
    ),
    'toggleable_guard_names' => 
    array (
      'roles' => 
      array (
        'isToggledHiddenByDefault' => true,
      ),
      'permissions' => 
      array (
        'isToggledHiddenByDefault' => true,
      ),
    ),
    'default_guard_name' => NULL,
    'should_show_guard' => true,
    'model_filter_key' => 'return \'%\'.$value;',
    'user_name_column' => 'name',
    'user_name_searchable_columns' => 
    array (
      0 => 'name',
    ),
    'icons' => 
    array (
      'role_navigation' => 'heroicon-o-lock-closed',
      'permission_navigation' => 'heroicon-o-lock-closed',
    ),
    'sort' => 
    array (
      'role_navigation' => false,
      'permission_navigation' => false,
    ),
    'generator' => 
    array (
      'guard_names' => 
      array (
        0 => 'web',
        1 => 'api',
      ),
      'permission_affixes' => 
      array (
        'viewAnyPermission' => 'view-any',
        'viewPermission' => 'view',
        'createPermission' => 'create',
        'updatePermission' => 'update',
        'deletePermission' => 'delete',
        'deleteAnyPermission' => 'delete-any',
        'replicatePermission' => 'replicate',
        'restorePermission' => 'restore',
        'restoreAnyPermission' => 'restore-any',
        'reorderPermission' => 'reorder',
        'forceDeletePermission' => 'force-delete',
        'forceDeleteAnyPermission' => 'force-delete-any',
      ),
      'permission_name' => 'return $permissionAffix . \' \' . $modelName;',
      'discover_models_through_filament_resources' => false,
      'model_directories' => 
      array (
        0 => 'C:\\xampp\\htdocs\\sedyco\\app\\Models',
      ),
      'custom_models' => 
      array (
      ),
      'excluded_models' => 
      array (
      ),
      'excluded_policy_models' => 
      array (
        0 => 'App\\Models\\User',
      ),
      'custom_permissions' => 
      array (
      ),
      'user_model' => 'App\\Models\\User',
      'policies_namespace' => 'App\\Policies',
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\xampp\\htdocs\\sedyco\\storage\\app',
        'throw' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\xampp\\htdocs\\sedyco\\storage\\app/public',
        'url' => 'http://localhost/storage',
        'visibility' => 'public',
        'throw' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '7ca6cb8f4be144206acbbd8d41717533',
        'secret' => '90d1f38634f1e5bd99f88d72e8d69b068f4deb73a9ed354db256261c3865aee3',
        'region' => 'auto',
        'bucket' => 'fls-9ea28291-f825-493f-9b11-b332260a8397',
        'url' => 'https://fls-9ea28291-f825-493f-9b11-b332260a8397.laravel.cloud',
        'endpoint' => 'https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com',
        'use_path_style_endpoint' => false,
        'throw' => false,
      ),
      'sedyco_disk' => 
      array (
        'driver' => 's3',
        'key' => '7ca6cb8f4be144206acbbd8d41717533',
        'secret' => '90d1f38634f1e5bd99f88d72e8d69b068f4deb73a9ed354db256261c3865aee3',
        'region' => 'auto',
        'bucket' => 'fls-9ea28291-f825-493f-9b11-b332260a8397',
        'url' => 'https://fls-9ea28291-f825-493f-9b11-b332260a8397.laravel.cloud',
        'endpoint' => 'https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com',
        'use_path_style_endpoint' => false,
        'throw' => false,
      ),
      'livewire-tmp' => 
      array (
        'driver' => 's3',
        'root' => 'C:\\xampp\\htdocs\\sedyco\\storage\\app/livewire-tmp',
      ),
      'filament-excel' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\xampp\\htdocs\\sedyco\\storage\\app/filament-excel',
        'url' => 'http://localhost/filament-excel',
      ),
    ),
    'links' => 
    array (
      'C:\\xampp\\htdocs\\sedyco\\public\\storage' => 'C:\\xampp\\htdocs\\sedyco\\storage\\app/public',
    ),
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => 12,
      'verify' => true,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'livewire' => 
  array (
    'class_namespace' => 'App\\Livewire',
    'view_path' => 'C:\\xampp\\htdocs\\sedyco\\resources\\views/livewire',
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'pagination_theme' => 'tailwind',
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\logs/laravel.log',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'url' => NULL,
        'host' => 'smtp-mail.outlook.com',
        'port' => '587',
        'encryption' => 'tls',
        'username' => 'sedyco@adcentrales.com',
        'password' => 'B(813157872849ot',
        'timeout' => NULL,
        'local_domain' => NULL,
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
      ),
      'mailgun' => 
      array (
        'transport' => 'mailgun',
      ),
    ),
    'from' => 
    array (
      'address' => 'sedyco@adcentrales.com',
      'name' => 'SEDyCO adc',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => 'C:\\xampp\\htdocs\\sedyco\\resources\\views/vendor/mail',
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'queue' => 
  array (
    'default' => 'sync',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '7ca6cb8f4be144206acbbd8d41717533',
        'secret' => '90d1f38634f1e5bd99f88d72e8d69b068f4deb73a9ed354db256261c3865aee3',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'auto',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'localhost',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'ses' => 
    array (
      'key' => '7ca6cb8f4be144206acbbd8d41717533',
      'secret' => '90d1f38634f1e5bd99f88d72e8d69b068f4deb73a9ed354db256261c3865aee3',
      'region' => 'auto',
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'mailgun' => 
    array (
      'domain' => NULL,
      'secret' => NULL,
      'endpoint' => 'api.mailgun.net',
      'scheme' => 'https',
    ),
    'pdfshift' => 
    array (
      'api_key' => 'sk_24425d5dfcc07202c594545981debafd108d4cd6',
    ),
    'ilovepdf' => 
    array (
      'api_key' => 'secret_key_5e9597987ed39e06abf3239cef99f619_0A7IXb2567f52af9f60625139c59875993d28',
      'public_key' => 'project_public_42a3dc90117b0b0c878bf5dadef062ab_uETqR23adecde4907cf9f9b4c0e38b9ce8a01',
    ),
  ),
  'session' => 
  array (
    'driver' => 'file',
    'lifetime' => '120',
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => 'C:\\xampp\\htdocs\\sedyco\\storage\\framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'sedyco_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\xampp\\htdocs\\sedyco\\resources\\views',
    ),
    'compiled' => 'C:\\xampp\\htdocs\\sedyco\\storage\\framework\\views',
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'dompdf' => 
  array (
    'show_warnings' => false,
    'public_path' => NULL,
    'convert_entities' => true,
    'options' => 
    array (
      'font_dir' => 'C:\\xampp\\htdocs\\sedyco\\storage\\fonts',
      'font_cache' => 'C:\\xampp\\htdocs\\sedyco\\storage\\fonts',
      'temp_dir' => 'C:\\Users\\braul\\AppData\\Local\\Temp',
      'chroot' => 'C:\\xampp\\htdocs\\sedyco',
      'allowed_protocols' => 
      array (
        'data://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'file://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'http://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'https://' => 
        array (
          'rules' => 
          array (
          ),
        ),
      ),
      'artifactPathValidation' => NULL,
      'log_output_file' => NULL,
      'enable_font_subsetting' => false,
      'pdf_backend' => 'CPDF',
      'default_media_type' => 'screen',
      'default_paper_size' => 'a4',
      'default_paper_orientation' => 'portrait',
      'default_font' => 'serif',
      'dpi' => 96,
      'enable_php' => false,
      'enable_javascript' => true,
      'enable_remote' => false,
      'allowed_remote_hosts' => NULL,
      'font_height_ratio' => 1.1,
      'enable_html5_parser' => true,
    ),
  ),
  'blade-heroicons' => 
  array (
    'prefix' => 'heroicon',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-google-material-design-icons' => 
  array (
    'prefix' => 'gmdi',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-iconpark' => 
  array (
    'prefix' => 'iconpark',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-solar-icons' => 
  array (
    'prefix' => 'solar',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-teeny-icons' => 
  array (
    'prefix' => 'tni',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => 'C:\\xampp\\htdocs\\sedyco\\storage\\framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'blade-fontawesome' => 
  array (
    'brands' => 
    array (
      'prefix' => 'fab',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'regular' => 
    array (
      'prefix' => 'far',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'solid' => 
    array (
      'prefix' => 'fas',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'duotone' => 
    array (
      'prefix' => 'fad',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'light' => 
    array (
      'prefix' => 'fal',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'thin' => 
    array (
      'prefix' => 'fat',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'sharp-light' => 
    array (
      'prefix' => 'fal:sharp',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'sharp-regular' => 
    array (
      'prefix' => 'far:sharp',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'sharp-solid' => 
    array (
      'prefix' => 'fas:sharp',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'sharp-duotone-solid' => 
    array (
      'prefix' => 'fad:sharp',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'sharp-thin' => 
    array (
      'prefix' => 'fat:sharp',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
    'custom' => 
    array (
      'prefix' => 'fak',
      'fallback' => '',
      'class' => '',
      'attributes' => 
      array (
      ),
    ),
  ),
  'flare' => 
  array (
    'key' => NULL,
    'flare_middleware' => 
    array (
      0 => 'Spatie\\FlareClient\\FlareMiddleware\\RemoveRequestIp',
      1 => 'Spatie\\FlareClient\\FlareMiddleware\\AddGitInformation',
      2 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddNotifierName',
      3 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddEnvironmentInformation',
      4 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionInformation',
      5 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddDumps',
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddLogs' => 
      array (
        'maximum_number_of_collected_logs' => 200,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddQueries' => 
      array (
        'maximum_number_of_collected_queries' => 200,
        'report_query_bindings' => true,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddJobs' => 
      array (
        'max_chained_job_reporting_depth' => 5,
      ),
      6 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddContext',
      7 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionHandledStatus',
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestBodyFields' => 
      array (
        'censor_fields' => 
        array (
          0 => 'password',
          1 => 'password_confirmation',
        ),
      ),
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestHeaders' => 
      array (
        'headers' => 
        array (
          0 => 'API-KEY',
          1 => 'Authorization',
          2 => 'Cookie',
          3 => 'Set-Cookie',
          4 => 'X-CSRF-TOKEN',
          5 => 'X-XSRF-TOKEN',
        ),
      ),
    ),
    'send_logs_as_events' => true,
  ),
  'ignition' => 
  array (
    'editor' => 'phpstorm',
    'theme' => 'auto',
    'enable_share_button' => true,
    'register_commands' => false,
    'solution_providers' => 
    array (
      0 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\BadMethodCallSolutionProvider',
      1 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\MergeConflictSolutionProvider',
      2 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\UndefinedPropertySolutionProvider',
      3 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\IncorrectValetDbCredentialsSolutionProvider',
      4 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingAppKeySolutionProvider',
      5 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\DefaultDbNameSolutionProvider',
      6 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\TableNotFoundSolutionProvider',
      7 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingImportSolutionProvider',
      8 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\InvalidRouteActionSolutionProvider',
      9 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\ViewNotFoundSolutionProvider',
      10 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\RunningLaravelDuskInProductionProvider',
      11 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingColumnSolutionProvider',
      12 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownValidationSolutionProvider',
      13 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingMixManifestSolutionProvider',
      14 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingViteManifestSolutionProvider',
      15 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingLivewireComponentSolutionProvider',
      16 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UndefinedViewVariableSolutionProvider',
      17 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\GenericLaravelExceptionSolutionProvider',
      18 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\OpenAiSolutionProvider',
      19 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\SailNetworkSolutionProvider',
      20 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMysql8CollationSolutionProvider',
      21 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMariadbCollationSolutionProvider',
    ),
    'ignored_solution_providers' => 
    array (
    ),
    'enable_runnable_solutions' => NULL,
    'remote_sites_path' => 'C:\\xampp\\htdocs\\sedyco',
    'local_sites_path' => '',
    'housekeeping_endpoint_prefix' => '_ignition',
    'settings_file_path' => '',
    'recorders' => 
    array (
      0 => 'Spatie\\LaravelIgnition\\Recorders\\DumpRecorder\\DumpRecorder',
      1 => 'Spatie\\LaravelIgnition\\Recorders\\JobRecorder\\JobRecorder',
      2 => 'Spatie\\LaravelIgnition\\Recorders\\LogRecorder\\LogRecorder',
      3 => 'Spatie\\LaravelIgnition\\Recorders\\QueryRecorder\\QueryRecorder',
    ),
    'open_ai_key' => NULL,
    'with_stack_frame_arguments' => true,
    'argument_reducers' => 
    array (
      0 => 'Spatie\\Backtrace\\Arguments\\Reducers\\BaseTypeArgumentReducer',
      1 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ArrayArgumentReducer',
      2 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StdClassArgumentReducer',
      3 => 'Spatie\\Backtrace\\Arguments\\Reducers\\EnumArgumentReducer',
      4 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ClosureArgumentReducer',
      5 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeArgumentReducer',
      6 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeZoneArgumentReducer',
      7 => 'Spatie\\Backtrace\\Arguments\\Reducers\\SymphonyRequestArgumentReducer',
      8 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\ModelArgumentReducer',
      9 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\CollectionArgumentReducer',
      10 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StringableArgumentReducer',
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
  ),
);
