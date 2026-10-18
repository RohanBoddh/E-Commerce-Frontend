<?php
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

if (class_exists('Dotenv\Dotenv')) {
    try {
        Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
    } catch (Throwable $e) {}
}

$mongoUri = $_ENV['MONGODB_URI'] ?? getenv('MONGODB_URI');
$databaseName = ($_ENV['MONGODB_DATABASE'] ?? $_ENV['MONGODB_DB_NAME'] ?? getenv('MONGODB_DATABASE') ?? getenv('MONGODB_DB_NAME')) ?: 'college_shopping';

if ($mongoUri === false || trim($mongoUri) === '') {
    throw new RuntimeException('MONGODB_URI is not configured. Set it in the PHP environment.');
}

$mongoClient = new Client($mongoUri);
$db = $mongoClient->selectDatabase($databaseName);

function docToArray($doc): array {
    if ($doc === null) return [];

    $arr = (array) $doc->jsonSerialize();

    if (isset($arr['_id']) && $arr['_id'] instanceof ObjectId) {
        $arr['id'] = (string) $arr['_id'];
    }

    foreach ($arr as $key => $value) {
        if ($value instanceof ObjectId) {
            $arr[$key] = (string) $value;
        } elseif ($value instanceof UTCDateTime) {
            $arr[$key] = date('Y-m-d H:i:s', $value->toDateTime()->getTimestamp());
        }
    }

    return $arr;
}

function cursorToArray($cursor): array {
    $results = [];
    foreach ($cursor as $doc) {
        $results[] = docToArray($doc);
    }
    return $results;
}

function toObjectId(string $id): ?ObjectId {
    try {
        return new ObjectId($id);
    } catch (InvalidArgumentException $exception) {
        return null;
    }
}

function nowUTC(): UTCDateTime {
    return new UTCDateTime();
}
