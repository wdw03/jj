<?php
// Clear screen function
function clearScreen() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

// Print colored message
function printColored($message, $colorCode) {
    echo "\033[{$colorCode}m$message\033[0m\n";
}

function printGreen($message) {
    printColored($message, '1;32');
}

function printRed($message) {
    printColored($message, '1;31');
}

// Extract ID from referral link
function extractReferralId($link) {
    if (preg_match('/startapp=f(\d+)/', $link, $matches)) {
        return $matches[1];
    }
    return false;
}

// Safely load JSON data from a file
function loadJsonFile($filePath) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// Safely save JSON data to a file
function saveJsonFile($filePath, $data) {
    $tempFilePath = "$filePath.tmp";
    file_put_contents($tempFilePath, json_encode($data, JSON_PRETTY_PRINT));
    rename($tempFilePath, $filePath);
}

// Clear screen initially
clearScreen();

// Print welcome messages
printGreen(". Welcome to Not Pixel User Manager");
printGreen(". Copy your Not Pixel referral link");
printGreen(". Multiple accounts supported\n");

// File to store user data
$usersFile = 'users.json';
$users = loadJsonFile($usersFile);

while (true) {
    printGreen("Options:");
    printGreen("1. Add new referral link");
    printGreen("2. View saved IDs");
    printGreen("3. Delete an ID");
    printGreen("4. Exit");
    printGreen("Select an option (1-4):");

    $option = trim(fgets(STDIN));

    switch ($option) {
        case '1':
            printGreen("Please paste your Not Pixel referral link:");
            $referralLink = trim(fgets(STDIN));

            $userId = extractReferralId($referralLink);

            if (!$userId) {
                printRed("Error: Invalid Not Pixel referral link! Please try again.");
                break;
            }

            if (isset($users[$userId])) {
                printRed("Error: ID already saved!");
                $userData = $users[$userId];
                printGreen("User ID: {$userId}\nSaved At: {$userData['saved_at']}");
                break;
            }

            $users[$userId] = [
                'tg_id' => $userId,
                'saved_at' => date('Y-m-d H:i:s')
            ];

            saveJsonFile($usersFile, $users);
            printGreen("Success: ID saved!");
            break;

        case '2':
            if (empty($users)) {
                printRed("No IDs saved yet.");
                break;
            }

            printGreen("\nSaved IDs:");
            foreach ($users as $id => $data) {
                echo "ID: $id, Saved At: {$data['saved_at']}\n";
            }
            break;

        case '3':
            printGreen("Enter the ID to delete:");
            $deleteId = trim(fgets(STDIN));

            if (!isset($users[$deleteId])) {
                printRed("Error: ID not found.");
                break;
            }

            unset($users[$deleteId]);
            saveJsonFile($usersFile, $users);
            printGreen("Success: ID deleted.");
            break;

        case '4':
            printGreen("Exiting... Goodbye!");
            exit;

        default:
            printRed("Invalid option. Please try again.");
    }

    printGreen("\nPress Enter to continue...");
    fgets(STDIN);
    clearScreen();
}
?>
