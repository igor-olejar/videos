<?php

class UserModel
{
    public int $id;
    public string $name;
    public string $email;
    public string $bio;
    public DateTime $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->bio = $data['bio'];
        $this->createdAt = new DateTime($data['created_at']);
    }
}

// 1. Generate dummy data (10,000 rows)
$rawData = [];
for ($i = 0; $i < 10000; $i++) {
    $rawData[] = [
        'id' => $i,
        'name' => 'User ' . $i,
        'email' => "user$i@example.com",
        'bio' => str_repeat("This is a long bio for user $i. ", 5),
        'created_at' => '2026-01-01 10:00:00'
    ];
}

// --- TEST 1: RAW ARRAYS ---
$start = microtime(true);
$arrayResult = [];
foreach ($rawData as $row) {
    $arrayResult[] = $row; // Just moving data
}
$end = microtime(true);
$arrayTime = $end - $start;
echo "RAW ARRAY METHOD:  " . number_format($arrayTime, 4) . "s\n";

// --- TEST 2: OBJECT HYDRATION ---
$start = microtime(true);
$objectResult = [];
foreach ($rawData as $row) {
    $objectResult[] = new UserModel($row); // Instantiation + Logic
}
$end = microtime(true);
$objectTime = $end - $start;
echo "OBJECT HYDRATION:   " . number_format($objectTime, 4) . "s\n";

// --- RESULTS ---
$ratio = round($objectTime / $arrayTime, 1);
echo "-------------------------------\n";
echo "The Hydration Tax is {$ratio}x slower.\n";
