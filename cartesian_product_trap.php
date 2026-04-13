<?php
// A dummy class to simulate a Laravel/Symfony Model
class PostModel {
    public function __construct(public $content, public $comment) {}
}

$pdo = new PDO('sqlite::memory:');
$pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, content TEXT)");
$pdo->exec("CREATE TABLE comments (post_id INTEGER, body TEXT)");

// 1. Make the post HEAVY (100KB of text)
$heavyContent = str_repeat("DUMMY DATA ", 10000); 
$pdo->prepare("INSERT INTO posts (content) VALUES (?)")->execute([$heavyContent]);

// 2. Add 1,000 comments
$stmt = $pdo->prepare("INSERT INTO comments (post_id, body) VALUES (1, ?)");
for ($i = 0; $i < 1000; $i++) {
    $stmt->execute(["Comment #$i"]);
}

// --- METHOD 1: THE JOIN ---
$start = microtime(true);
$res = $pdo->query("SELECT p.content, c.body FROM posts p JOIN comments c ON p.id = c.post_id");

$objects = [];
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $objects[] = new PostModel($row['content'], $row['body']);
}
$timeJoin = microtime(true) - $start;
echo "JOIN METHOD: " . number_format($timeJoin, 4) . "s (Memory peak: " . (memory_get_peak_usage() / 1024 / 1024) . " MB)\n";

// Clear memory for fair test
unset($objects);
gc_collect_cycles();

// --- METHOD 2: TWO QUERIES ---
$start = microtime(true);
$post = $pdo->query("SELECT content FROM posts WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$comments = $pdo->query("SELECT body FROM comments WHERE post_id = 1")->fetchAll(PDO::FETCH_ASSOC);

$optimizedObjects = [];
foreach ($comments as $c) {
    $optimizedObjects[] = new PostModel($post['content'], $c['body']);
}
$timeTwo = microtime(true) - $start;
echo "TWO-QUERY METHOD: " . number_format($timeTwo, 4) . "s (Current memory usage: " . (memory_get_usage() / 1024 / 1024) . " MB)\n";
