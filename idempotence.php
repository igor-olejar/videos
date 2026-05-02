// DANGER: No memory of previous attempts
function processPayment($userId, $amount) {
    // 1. Charge the credit card via API
    $paymentGateway->charge($userId, $amount);

    // 2. Update the local database
    $db->query("UPDATE users SET balance = balance - $amount WHERE id = $userId");

    return "Success";
}

// If the network dies after step 1 but before the return, 
// the client retries and the user is charged twice.


// ------------------------------------------------------

function processIdempotentPayment($idempotencyKey, $userId, $amount) {
    // 1. Check if we've already handled this specific request
    $existingResponse = $db->query(
        "SELECT response_body FROM idempotency_keys WHERE key = ?", 
        [$idempotencyKey]
    )->fetch();

    if ($existingResponse) {
        // Return the exact same receipt we generated the first time
        return $existingResponse['response_body'];
    }

    // 2. Perform the side effects (Charge + DB Update)
    // Wrap in a transaction for "All or Nothing" safety
    $db->transaction(function() use ($userId, $amount, $idempotencyKey) {
        $paymentGateway->charge($userId, $amount);
        $db->query("UPDATE users SET balance = balance - $amount WHERE id = $userId");
        
        // 3. Record the result so we don't repeat it
        $result = "Success: Charged $amount at " . date('Y-m-d H:i:s');
        $db->query(
            "INSERT INTO idempotency_keys (key, response_body) VALUES (?, ?)", 
            [$idempotencyKey, $result]
        );
    });

    return $result;
}
