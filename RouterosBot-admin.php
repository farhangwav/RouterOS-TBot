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

// تابع برای خواندن وضعیت کاربر از پایگاه داده
function getUserState($chatId, $conn) {
    $stmt = $conn->prepare("SELECT state, username FROM user_states WHERE chat_id = ?");
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $stmt->bind_result($state, $username);
    $stmt->fetch();
    $stmt->close();
    return ['state' => $state, 'username' => $username];
}

// تابع برای ذخیره وضعیت کاربر در پایگاه داده
function setUserState($chatId, $state, $username, $conn) {
    $stmt = $conn->prepare("REPLACE INTO user_states (chat_id, state, username) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $chatId, $state, $username);
    $stmt->execute();
    $stmt->close();
}

// تابع برای ارسال پیام با کیبورد سفارشی
function sendInitialOptions($chatId, $apiBaseUrl) {
    $replyMarkup = [
        'keyboard' => [
            [['text' => 'تمدید'], ['text' => 'استعلام']]
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

    // تقسیم پیام به بخش‌های کوچکتر
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
    //اندازه گیری مقدار دانلود
  $download = $responseArray2[0]["total-download"];
  $download_number = floatval($download); // تبدیل رشته به عدد
  $downloadGB = $download_number / 1073741824; // انجام عملیات تقسیم
  $downloadGB_text = number_format($downloadGB, 2); // تبدیل نتیجه به رشته
  #sendMessageToTelegram("مقدار مصرف(دانلود):\n$downloadGB_text گیگابایت");

  //اندازه گیری مقدار آپلود
  $upload= $responseArray2[0]["total-upload"];
  $upload_number = floatval($upload); // تبدیل رشته به عدد
  $uploadGB = $upload_number / 1073741824; // انجام عملیات تقسیم
  $uploadGB_text = number_format($uploadGB, 2); // تبدیل نتیجه به رشته

  //اندازه گیری مقدار کل مصرف
  $total_used = $upload_number + $download_number;
  $total_usedGB = $total_used / 1073741824; // انجام عملیات تقسیم
  $total_usedGB_text = number_format($total_usedGB, 2); // تبدیل نتیجه به رشته

  #sendMessageToTelegram("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n");

  //اندازه گیری و نمایش باقیمانده
  if($responseArray2[0]["actual-profile"]=="1 Mounth 10 GB"){
      $total_remainig = 10737418240-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 20 GB"){
      $total_remainig = 21474836480-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 40 GB"){
      $total_remainig = 42949672960-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 50 GB"){
      $total_remainig = 53687091200-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 80 GB"){
      $total_remainig = 85899345920-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "1 Mounth 100 GB"){
      $total_remainig = 107374182400-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "user_test"){
      $total_remainig = 1073741824-$total_used;
      $total_remainigGB = $total_remainig / 1073741824; // انجام عملیات تقسیم
      $total_remainigGB_text = number_format($total_remainigGB, 2); // تبدیل نتیجه به رشته
      return ("مقدار مصرف(دانلود):  $downloadGB_text گیگابایت\n"."مقدار مصرف(آپلود):  $uploadGB_text گیگابایت\n"."مقدار کل مصرف:  $total_usedGB_text گیگابایت\n"."باقیمانده حجم بسته فعلی:  $total_remainigGB_text گیگابایت\n");
  } else if($responseArray2[0]["actual-profile"]== "unlimit"){
      return ("Unlimited Profile");
  } else {
      return ("این کاربر درحال حاضر بسته فعالی ندارید\n");
  }
}

function showTimeLeft($responseArray3){
$endTime = $responseArray3[0]["end-time"];
$state = $responseArray3[0]["state"];
if($state== "running-active"){
return ("تاریخ سررسید بسته‌ فعلی این کاربر:\n$endTime");


} else if($state== "running"){
return ("میزان مصرف حجمی بسته این کاربر به پایان رسیده و جهت استفاده از سرویس GSVPN می‌بایست بسته جدید تهیه فرمائید\nدرصورت تمایل به تمدید سرویس خود لطفا با ادمین در ارتباط باشید");
} else if($state== "used"){
return ("مهلت استفاده بسته این کاربر به پایان رسیده و جهت استفاده از سرویس GSVPN می‌بایست بسته جدید تهیه فرمائید\nدرصورت تمایل به تمدید سرویس خود لطفا با ادمین در ارتباط باشید");
} else if($state== "waiting"){
return ("بسته این کاربر درحالت رزرو قرار دارد و به محض اولین اتصال بسته فعال خواهد شد");
}
}


// دریافت ورودی JSON از تلگرام
$update = file_get_contents("php://input");
$updateArray = json_decode($update, true);

$chatId = $updateArray["message"]["chat"]["id"];
$message = $updateArray["message"]["text"];

# این if برای بررسی ChatID قرار داده شده که مبادا شخصی که نباید دسترسی داشته باشه از بات تلگرام استفاده کنه. 
# جهت استفاده، خط بعدی را به همراه else در پایان کد از حالت کامنت خارج کنید
#if ($chatId == "YOURE first ChatID" || $chatId == "YOURE Second ChatID") {

// ایجاد اتصال به پایگاه داده
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userState = getUserState($chatId, $conn);

// مدیریت وضعیت‌ها با استفاده از switch
switch ($userState['state']) {
    case null:
        // مرحله ۱: ارائه دو گزینه به کاربر
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;

    case 'awaiting_option':
        // مرحله ۲: دریافت گزینه و درخواست نام کاربری
        if ($message === 'تمدید') {
            file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=کدوم یوزر رو تمدید کنم؟");

            setUserState($chatId, 'awaiting_username_option_1', null, $conn);
        } elseif ($message === 'استعلام') {
            file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=کدوم یوزر رو استعلام کنم؟");
            setUserState($chatId, 'awaiting_username_option_2', null, $conn);
        }
        break;

    case 'awaiting_username_option_1':
        // مرحله ۳: دریافت نام کاربری و درخواست عدد
        setUserState($chatId, 'awaiting_number', $message, $conn);
        file_get_contents("$apiBaseUrl/sendMessage?chat_id=$chatId&text=پکیج چند گیگ؟");
        break;

    case 'awaiting_number':
        // مرحله ۴: دریافت عدد و نمایش نام کاربری و عدد
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
                sendCommandToMikrotik($API, "/user-manager/user/remove", ["numbers" => "$id"]);
                sendCommandToMikrotik($API, "/user-manager/user/add", ["name" => "$userName", "password" => "$userPassword", "shared-users" => "$sharedUsers" ]);
                sendMessageToTelegram("$userName Reset was successful!", $chatId);

                // User Profile Query
                $result = sendCommandToMikrotik($API, "/user-manager/user-profile/add", ["user" => "$userName", "profile" => "1 Mounth $package GB"]);
                if (strpos($result, '"!trap"') !== false) {
                    // اگر حاوی خطاست
                    sendMessageToTelegram("Error activating Package!", $chatId);
                    // استخراج پیام خطا
                    #preg_match('/"message":\s*"([^"]+)"/', $result, $matches);
                    #if (isset($matches[1])) {
                    #    sendMessageToTelegram("Error Message: $matches[1]", $chatId);
                    #}
                } else {
                    // اگر حاوی خطا نیست
                    sendMessageToTelegram("Package 1 Mounth $package GB successfully added to user $userName ", $chatId);
                }
            } else {
                sendMessageToTelegram("User not found", $chatId);
            }
            $API->disconnect(); // API Disconnect
        } else {
            sendMessageToTelegram("Unable to connect to Mikrotik router.", $chatId);
        }

        // بازگشت به مرحله ۱
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;

    case 'awaiting_username_option_2':
        // مرحله ۳: دریافت نام کاربری 
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

                    $messageToTelegram = "$sharedUser کاربره\n"."تعداد کاربر درحال استفاده: $activeSessions\n"."$usageMessage"."$timeLeftMessage"."\nآی‌دی تلگرام ادمین:\n@GSvpn_admin";

                    sendMessageToTelegram($messageToTelegram, $chatId);

            } else {
                sendMessageToTelegram("User not found", $chatId);
            }
            $API->disconnect(); // API Disconnect
        } else {
            sendMessageToTelegram("Unable to connect to Mikrotik router.", $chatId);
        }
        // بازگشت به مرحله ۱
        sendInitialOptions($chatId, $apiBaseUrl);
        setUserState($chatId, 'awaiting_option', null, $conn);
        break;
}

// بستن اتصال به پایگاه داده
$conn->close();

#} else {
#    sendMessageToTelegram("Sorry. You Are not allowed to send message to BOT!!!", $chatId);
#}

?>
