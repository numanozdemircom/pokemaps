<?php
require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

if ($_GET["step"] == "registration") {

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Invalid JSON."]));
    }

    $username = $input['username'];
    $raw_password = $input['password'] ?? '';
    $world_pref = isset($input['world_pref']) ? (int)$input['world_pref'] : 0;
    $gender = isset($input['gender']) ? (int)$input['gender'] : 0;
    $gps_location = htmlspecialchars($input['gps_location'] ?? '', ENT_QUOTES, 'UTF-8');
    $age = isset($input['age']) ? (int)$input['age'] : null;
    $zodiac_sign = htmlspecialchars($input['zodiac_sign'] ?? '', ENT_QUOTES, 'UTF-8');
    $hobbies = htmlspecialchars($input['hobbies'] ?? '', ENT_QUOTES, 'UTF-8');
    $profile_description = htmlspecialchars($input['profile_description'] ?? '', ENT_QUOTES, 'UTF-8');

    if (!$username || !$raw_password || $age === null || empty($zodiac_sign)) {
        http_response_code(422);
        die(json_encode(["success" => false, "message" => "Missing or invalid fields."]));
    }

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $checkStmt->execute([':username' => $username]);
    $usernameExists = $checkStmt->fetchColumn();

    if ($usernameExists) {
        http_response_code(409);
        die(json_encode(["success" => false, "message" => "This username is already registered. Please try logging in."]));
    }

    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (
                username, password_hash, world_pref, gender,
                gps_location, age, zodiac_sign, hobbies,
                profile_description
            ) VALUES (
                :username, :password_hash, :world_pref, :gender,
                :gps_location, :age, :zodiac_sign, :hobbies,
                :profile_description
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':world_pref' => $world_pref,
            ':gender' => $gender,
            ':gps_location' => $gps_location,
            ':age' => $age,
            ':zodiac_sign' => $zodiac_sign,
            ':hobbies' => $hobbies,
            ':profile_description' => $profile_description
        ]);

        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Registration successful! Welcome to PokeMaps!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error occurred."]);
    }
} elseif($_GET["step"] == "login") {


    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Invalid JSON."]));
    }

    $username = $input['username'];
    $raw_password = $input['password'] ?? '';

    if (!$username|| !$raw_password) {
        http_response_code(422);
        die(json_encode(["success" => false, "message" => "Username and password are required."]));
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($raw_password, $user['password_hash'])) {
            http_response_code(401);
            die(json_encode(["success" => false, "message" => "Invalid username or password."]));
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful! Welcome back to PokeMaps!",
            "user_id" => $user['user_id'] ?? null,
            "username" => $user['username']
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Database error occurred."]));
    }

} elseif($_GET["step"] == "list"){

    try {
        $stmt = $pdo->prepare("SELECT user_id, username, gender, gps_location, age, zodiac_sign, hobbies, profile_description FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "users" => $users
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error occurred."
        ]);
    }

} elseif($_GET['step'] == 'send_message'){
    $data = json_decode(file_get_contents("php://input"), true);
    $sender = $data['sender'];
    $receiver = $data['receiver'];
    $message = $data['message'];
    $timestamp = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO messages (sender, receiver, message, sent_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sender, $receiver, $message, $timestamp]);
    echo json_encode(["success" => true]);
    exit;
} elseif ($_GET['step'] == 'receive_messages') {
    $receiver = $_GET['receiver'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE receiver = ? ORDER BY sent_at DESC");
    $stmt->execute([$receiver]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
    exit;
} else {
	die(json_encode(["success" => false, "message" => "Invalid step specified."]));
}
?>
