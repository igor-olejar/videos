<?php

/**
 * BENCHMARK: The N+1 Query Problem (Pure PHP + PDO)
 * Scenario: Fetching 100 blog posts and their authors.
 */

// 1. Setup: Create an In-Memory Database and Dummy Data
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE authors (id INTEGER PRIMARY KEY, name TEXT)");
$pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT, author_id INTEGER)");

// Insert 50 Authors and 500 Posts
for ($i = 1; $i <= 50; $i++) {
    $pdo->prepare("INSERT INTO authors (name) VALUES (?)")->execute(["Author $i"]);
}
for ($i = 1; $i <= 500; $i++) {
    $authorId = rand(1, 50);
    $pdo->prepare("INSERT INTO posts (title, author_id) VALUES (?, ?)")->execute(["Blog Post $i", $authorId]);
}

echo "--- Starting Database Benchmark (500 Posts) ---\n\n";

// ---------------------------------------------------------
// METHOD 1: The "N+1" Way
// ---------------------------------------------------------
$queriesRun = 0;
$startSlow = microtime(true);

// Query 1: Get all posts
$stmt = $pdo->query("SELECT * FROM posts");
$queriesRun++; 
$posts = $stmt->fetchAll(PDO::FETCH_OBJ);

$resultsSlow = [];
foreach ($posts as $post) {
    // Query N: For EVERY post, we go back to the database for the author
    $authorStmt = $pdo->prepare("SELECT name FROM authors WHERE id = ?");
    $authorStmt->execute([$post->author_id]);
    $queriesRun++; 
    
    $author = $authorStmt->fetch(PDO::FETCH_OBJ);
    $resultsSlow[] = "{$post->title} by {$author->name}";
}

$timeSlow = microtime(true) - $startSlow;

echo "Method 1: N+1 (Lazy Loading)\n";
echo "Total Queries: $queriesRun\n";
echo "Time Taken: " . number_format($timeSlow, 4) . " seconds\n\n";


// ---------------------------------------------------------
// METHOD 2: The Eager Loading Way
// ---------------------------------------------------------
$queriesRun = 0;
$startFast = microtime(true);

// Query 1: Get all posts
$stmt = $pdo->query("SELECT * FROM posts");
$queriesRun++;
$posts = $stmt->fetchAll(PDO::FETCH_OBJ);

// Step A: Collect all unique Author IDs
$authorIds = array_unique(array_column($posts, 'author_id'));

// Query 2: Get all required authors in ONE shot
$placeholders = implode(',', array_fill(0, count($authorIds), '?'));
$authorStmt = $pdo->prepare("SELECT id, name FROM authors WHERE id IN ($placeholders)");
$authorStmt->execute(array_values($authorIds));
$queriesRun++;

// Step B: Map authors by their ID for O(1) lookup (like our last video!)
$authorsMap = [];
foreach ($authorStmt->fetchAll(PDO::FETCH_OBJ) as $author) {
    $authorsMap[$author->id] = $author->name;
}

// Step C: Link them up in memory
$resultsFast = [];
foreach ($posts as $post) {
    $authorName = $authorsMap[$post->author_id] ?? 'Unknown';
    $resultsFast[] = "{$post->title} by {$authorName}";
}

$timeFast = microtime(true) - $startFast;

echo "Method 2: Eager Loading (Optimized)\n";
echo "Total Queries: $queriesRun\n";
echo "Time Taken: " . number_format($timeFast, 4) . " seconds\n\n";
echo "RESULT: Method 2 cut down " . ($timeSlow / $timeFast > 1 ? number_format($timeSlow / $timeFast, 1) : 1) . "x the time by reducing 501 queries to just 2.\n";
