<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['DOCUMENT_ROOT'] === '/var/www/html') { $env = "aws"; }
else if ($_SERVER['DOCUMENT_ROOT'] === '/Applications/MAMP/htdocs') { $env = "local"; }
else { echo "<p>Cannot figure out server environment.</p>"; }

if ($env === "aws") { $auth = file_get_contents("/home/okta/password.txt"); }
else if ($env === "local") { $auth = file_get_contents("/Users/tomsmith/okta/password.txt"); }

// print_r($_POST);

$userData = '{
	"profile": {
		"firstName": "' . $_POST["firstName"] . '",
		"lastName":  "' . $_POST["lastName"]  . '",
		"email":     "' . $_POST["email"]     . '",
		"login":     "' . $_POST["login"]     . '"
	},
	"credentials": {
		"password": {
			"value": "' . $_POST["password"]  . '"
		}
	}
}';

echo "<p>Here is the data that was submitted by the user:</p>";

echo "<p>$userData</p>";

// Now let's try to take that user data and make a record in Okta

$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_POST => 1,
	CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'https://dev-528971-admin.oktapreview.com/api/v1/users?activate=true',
    CURLOPT_HTTPHEADER => array("Authorization: SSWS $auth ", "Accept: application/json", "Content-Type: application/json"),
    CURLOPT_POSTFIELDS => $userData
    ));

$result = curl_exec($curl);

$decodedResult = json_decode($result);

// print_r ($decodedResult);

if (property_exists($decodedResult, "id")) {
	echo "<p style='color:green'>user created successfully.</p>";
}
else {
	echo "<p style='color:red'>Something went wrong.</p>";
	echo "<pre>$result</pre>";
	curl_close($curl);
	exit;
}

// Now let's try to assign this user to Salesforce

$userData = '{
  "id": "' . $decodedResult->id . '",
  "scope": "USER",
  "credentials": {
    "userName": "' . $decodedResult->profile->login . '"
  },
  "profile": {
    "role": "Channel Sales Team",
    "profile": "Force.com - Free User"
  }  
}';

echo "<p>here is the user data we are going to submit to the api endpoint:";
echo "<pre>$userData</pre>";

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev-528971-admin.oktapreview.com/api/v1/apps/0oa659kccuncdERlu0h7/users',
    CURLOPT_POSTFIELDS => $userData
));

$result = curl_exec($curl);

$decodedResult = json_decode($result);

if (property_exists($decodedResult, "id")) {
	echo "<p style='color:green'>User added to Salesforce successfully.</p>";
}
else {
	echo "<p style='color:red'>Something went wrong.</p>";
	echo "<pre>$result</pre>";
}

curl_close($curl);

echo "<p><a href = 'https://dev-528971-admin.oktapreview.com/'>Log in to Okta</a></p>";