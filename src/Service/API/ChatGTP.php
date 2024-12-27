<?php
// Define the API endpoint and your API key
$api_url = "https://api.openai.com/v1/chat/completions";
$api_key = "your-api-key-here"; // Replace with your OpenAI API key

// Set up the data payload
$data = [
    "model" => "gpt-4", // Specify the model
    "messages" => [
        ["role" => "system", "content" => "You are ChatGPT, a helpful assistant."],
        ["role" => "user", "content" => "What is the capital of France?"]
    ],
    "max_tokens" => 100,
    "temperature" => 0.7
];

// Initialize cURL session
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Execute cURL request and fetch response
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL error: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Decode and display the response
$response_data = json_decode($response, true);

if (isset($response_data['choices'][0]['message']['content'])) {
    echo "ChatGPT Response: " . $response_data['choices'][0]['message']['content'];
} else {
    echo "Error in API response: " . $response;
}
?>