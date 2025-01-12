<?php
/**
 * Anthropic API Client with HTTP/2 support
 *
 * Suggested usage:
 * $client = new AnthropicClient('your-api-key-here');
 * $response = $client->createMessage('Tell me a joke');
 * echo $response['content'][0]['text'];
 */
 
namespace Drupal\drupalai\Service\API;

 
class AnthropicClient {
    private $baseUrl = 'https://api.anthropic.com/v1/messages';
    private static $model_options = ['claude_haiku_2341' => 'Claude Haiku', 'claude_sonnet_342' => 'Claude Sonnet'];
    private $model;
    private $apiKey;

    public function __construct($model) {
        $this->model = $model;        
        $this->apiKey = $this->getClaudeApiKey();
    }
    
    private function getClaudeApiKey() {
        return trim(file_get_contents('/home/master/api_keys/claude_api_key.txt'));
    }
    
    public static function getModelOptions() {
        return self::$model_options;
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
        
        // HTTP/2 specific settings
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        
        // General settings
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Additional error handling settings
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            
            throw new Exception(sprintf(
                "Curl error: %s\nVerbose log:\n%s",
                curl_error($ch),
                $verboseLog
            ));
        }
        
        fclose($verbose);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('API request failed with status code: ' . $httpCode . "\nResponse: " . $response);
        }

        return json_decode($response, true);
    }
}


// Example usage:
try {
    $model = AnthropicClient::getModelOptions();
    
    $client = new AnthropicClient($model);
    $response = $client->createMessage('Claude, what is your take on the nature of consciousness?');
    
    if (isset($response['content'][0]['text'])) {
        echo $response['content'][0]['text'];
    } else {
        echo "Unexpected response format for Claude.\n";
        print_r($response); // Print full response for debugging
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}