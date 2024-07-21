<?php
#This Bot requires routeros-api-master API
#You can find it Here:
#https://github.com/socialwifi/RouterOS-api/tree/master
require('routeros-api-master/routeros_api.class.php');

// Set your bot token and webhook URL
$botToken = 'Your Bot Token';
$apiBaseUrl = "https://api.telegram.org/bot{$botToken}";

$mikrotikIp = "YOUR Router Address";
$mikrotikUser = "Router User";
$mikrotikPassword = "Router User Password";

$servername = "localhost";
$dbuser = "dbuser";
$dbpass = "dbpass";
$dbname = "dbname";

/*
*
*Afret creating the Database, Use this query to initiate it:
*
*   CREATE TABLE user_states (
*       chat_id BIGINT PRIMARY KEY,
*       state VARCHAR(50),
*       username VARCHAR(100)
*   );
*
*
*/

// Function to read User state from database
function getUserState($chatId, $conn) {
    $stmt = $conn->prepare("SELECT state, username FROM user_states WHERE chat_id = ?");
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $stmt->bind_result($state, $username);
    $stmt->fetch();
    $stmt->close();
    return ['state' => $state, 'username' => $username];
}

// Function to save User state to database
function setUserState($chatId, $state, $username, $conn) {
    $stmt = $conn->prepare("REPLACE INTO user_states (chat_id, state, username) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $chatId, $state, $username);
    $stmt->execute();
    $stmt->close();
}

// Keyboard Function
function sendInitialOptions($chatId, $apiBaseUrl) {
    $replyMarkup = [
        'keyboard' => [
            [['text' => 'revival'], ['text' => 'inquiry']]
        ],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ];
    file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=Please choose an option&reply_markup=" . json_encode($replyMarkup));
}

function sendCommandToMikrotik($API, $command, $params = []) {
    $response = $API->comm($command, $params);
    return json_encode($response, JSON_PRETTY_PRINT);
}

function sendMessageToTelegram($message, $chatId) {
    global $botToken;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $maxLength = 4096;

    // Split the Message to suitable parts for Telegram
    $messageParts = str_split($message, $maxLength);

    foreach ($messageParts as $part) {
        $postFields = [
            'chat_id' => $chatId,
            'text' => $part
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        curl_close($ch);
    }
}

function showUsage($responseArray2) {
    //Calculating Downloads
  $download = $responseArray2[0]["total-download"];
  $download_number = floatval($download); // String to integer
  $downloadGB = $download_number / 1073741824; // Bytes to GigaBytes
  $downloadGB_text = number_format($downloadGB, 2); // Result to String
  #sendMessageToTelegram("Download Usages:\n$downloadGB_text GB");

  //Calculating Uploads
  $upload= $responseArray2[0]["total-upload"];
  $upload_number = floatval($upload);
  $uploadGB = $upload_number / 1073741824;
  $uploadGB_text = number_format($uploadGB, 2);

  //Calculating Overall Usage
  $total_used = $upload_number + $download_number;
  $total_usedGB = $total_used / 1073741824;
  $total_usedGB_text = number_format($total_usedGB, 2);

  #sendMessageToTelegram("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");

  //Calculating remaining GigaBytes base on the user Package
  if($responseArray2[0]["actual-profile"]=="1 Mounth 10 GB"){
      $total_remainig = 10737418240-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 20 GB"){
      $total_remainig = 21474836480-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 40 GB"){
      $total_remainig = 42949672960-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 50 GB"){
      $total_remainig = 53687091200-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 80 GB"){
      $total_remainig = 85899345920-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 100 GB"){
      $total_remainig = 107374182400-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "user_test"){
      $total_remainig = 1073741824-$total_used;
      $total_remainigGB = $total_remainig / 1073741824;
      $total_remainigGB_text = number_format($total_remainigGB, 2);
      return ("Download Usage:  $downloadGB_text GB\n"."Upload Usage:  $uploadGB_text GB\n"."Total Used:  $total_usedGB_text GB\n");
  } else if($responseArray2[0]["actual-profile"]== "unlimit"){
      return ("Unlimited Profile");
  } else {
      return ("No Active Package\n");
  }
}

function showTimeLeft($responseArray3){
$endTime = $responseArray3[0]["end-time"];
$state = $responseArray3[0]["state"];
if($state== "running-active"){
return ("UserPackage Expire Date:\n$endTime");


} else if($state== "running"){
return ("Maximum bandwith used");
} else if($state== "used"){
return ("Package Expired");
} else if($state== "waiting"){
return ("Package is reseved and ready to be used");
}
}


// Receive JSON input from Telegram
$update = file_get_contents("php://input");
$updateArray = json_decode($update, true);

// Gathering Sender ChatID and Message
$chatId = $updateArray["message"]["chat"]["id"];
$message = $updateArray["message"]["text"];

# Security acction: 
# Uncomment it if you want to have conversation with bot with only specific ChatIDs
# if ($chatId == "YOURE first ChatID" || $chatId == "YOURE Second ChatID") {

// Connectin to DB
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);

// Checking the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userState = getUserState($chatId, $conn);

// switch: Checking and Updating UserState every single time
switch ($userState['state']) {
    case null:
        // Step1: User choosing option 1 or 2
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;

    case 'awaiting_option':
        // Step1: Getting the UserName
        if ($message === 'revival') {
            file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=Pls Enter The User Name to ");

            setUserState($chatId, 'awaiting_username_option_1', null, $conn);
        } elseif ($message === 'inquiry') {
            file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=Pls Enter The User Name");
            setUserState($chatId, 'awaiting_username_option_2', null, $conn);
        }
        break;

    case 'awaiting_username_option_1':
        // Option1/Step3: getting Package Name
        setUserState($chatId, 'awaiting_number', $message, $conn);
        file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=Pls Enter the Package Name");
        break;

    case 'awaiting_number':
        // Option1/Step4: Reseting the user statistics and adding package to it
        $userName = $userState['username'];
        $package = $message;

        $API = new RouterosAPI();
        if ($API->connect($mikrotikIp, $mikrotikUser, $mikrotikPassword)) {
            $response = sendCommandToMikrotik($API, "/user-manager/user/print", ["?name" => "$userName"]);
            $responseArray = json_decode($response, true);

            if (isset($responseArray[0][".id"])) {
                $id = $responseArray[0][".id"];
                $userPassword = $responseArray[0]["password"];
                $sharedUsers = $responseArray[0]["shared-users"];

    
                // Reset User Statistics
                // In Mikrotik UserManager we have to delete the user and create it again to reset the users statistics, so be it:
                sendCommandToMikrotik($API, "/user-manager/user/remove", ["numbers" => "$id"]); # removing the user
                sendCommandToMikrotik($API, "/user-manager/user/add", ["name" => "$userName", "password" => "$userPassword", "shared-users" => "$sharedUsers" ]); # recreating the user by gathered information before deleting
                sendMessageToTelegram("$userName Reset was successful!", $chatId);

                // User Profile Query
                $result = sendCommandToMikrotik($API, "/user-manager/user-profile/add", ["user" => "$userName", "profile" => "$package"]);
                
                if (strpos($result, '"!trap"') !== false) { // If there is no package named as $package, Error comes out
                    
                    sendMessageToTelegram("Error activating Package!", $chatId);
                    // by uncommenting the following codes, we can have the error log in resonse to the bot
                    #preg_match('/"message":\s*"([^"]+)"/', $result, $matches);
                    #if (isset($matches[1])) {
                    #    sendMessageToTelegram("Error Message: $matches[1]", $chatId);
                    #}
                } else { // If The package name is correct:

                    sendMessageToTelegram("Package $package successfully added to user $userName ", $chatId);
                }
            } else {
                sendMessageToTelegram("User not found", $chatId);
            }
            $API->disconnect(); // API Disconnect
        } else {
            sendMessageToTelegram("Unable to connect to Mikrotik router.", $chatId);
        }

        // return to Step1
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;

    case 'awaiting_username_option_2':
        // Option2/Step3: getting username
        $API = new RouterosAPI();
        if ($API->connect($mikrotikIp, $mikrotikUser, $mikrotikPassword)) {
            $userName = $message;
            $response = sendCommandToMikrotik($API, "/user-manager/user/print", ["?name" => "$userName"]);
            $responseArray = json_decode($response, true);
            if (isset($responseArray[0][".id"])) {
                $id = $responseArray[0][".id"];
                    // User Information Query
                    $response2 = sendCommandToMikrotik($API, "/user-manager/user/monitor", ["numbers" => "$id", "once" => ""]);
                    // User Profile Query
                    $response3 = sendCommandToMikrotik($API, "/user-manager/user-profile/print", ["where" => "", "?user" => "$userName"]);

                    $responseArray2 = json_decode($response2, true);
                    $responseArray3 = json_decode($response3, true);
                    $usageMessage = showUsage($responseArray2);
                    $timeLeftMessage = showTimeLeft($responseArray3);

                    $sharedUser = $responseArray[0]["shared-users"];
                    $activeSessions = $responseArray2[0]["active-sessions"];

                    $messageToTelegram = "$sharedUser Shared-Users\n"."Active accounts right now: $activeSessions\n"."$usageMessage"."$timeLeftMessage"."\n";

                    sendMessageToTelegram($messageToTelegram, $chatId);

            } else {
                sendMessageToTelegram("User not found", $chatId);
            }
            $API->disconnect(); // API Disconnect
        } else {
            sendMessageToTelegram("Unable to connect to Mikrotik router.", $chatId);
        }
        // return to Step1
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;
}

// Closing the database connection
$conn->close();

#} else {
#    sendMessageToTelegram("Sorry. You Are not allowed to send message to BOT!!!", $chatId);
#}

?>
