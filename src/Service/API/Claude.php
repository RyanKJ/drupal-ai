<?php

 /**
  *
  * Suggested usage
  *
  * $client = new AnthropicClient('your-api-key-here');
  * $response = $client->createMessage('Tell me a joke');
  * echo $response['content'][0]['text'];
  *
  *
  *
  */
class AnthropicClient {
    private $apiKey;
    private $baseUrl = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-haiku-20240307'; // 'claude-3-sonnet-20240229'; 

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function createMessage($prompt) {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ];

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1024
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('API request failed with status code: ' . $httpCode);
        }

        return json_decode($response, true);
    }
}

function getClaudeApiKey() {
  return file_get_contents('/home/master/api_keys/claude_api_key.txt'); 
}


// Example usage:
try {
    $api_key = getClaudeApiKey();
    $client = new AnthropicClient($api_key);
    $response = $client->createMessage('What is the capital of France?');
    
    if (isset($response['content'][0]['text'])) {
        echo $response['content'][0]['text'];
    } else {
        echo "Unexpected response format";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}