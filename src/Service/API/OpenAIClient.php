<?php
/**
 * OpenAI API Client with HTTP/2 support
 *
 * Suggested usage:
 * $client = new OpenAIClient('your-api-key-here');
 * $response = $client->createMessage('Tell me a joke');
 * echo $response;
 */
 
namespace Drupal\drupalai\Service\API;

use \Exception;


/**
 * Class that calls and interfaces with OpenAI ChatGPT LLM
 */
class OpenAIClient {
    private $baseUrl = 'https://api.openai.com/v1/chat/completions';
    private static $model_options = ['gpt-3.5-turbo' => 'ChatGPT 3.5', 'gpt-4' => 'ChatGPT 4.0'];
    private $model;
    private $apiKey;
    
    /**
     * Method to fetch various models of the API
     */
    public static function getModelOptions() {
        return self::$model_options;
    }

    public function __construct($model) {
        $this->model = $model;        
        $this->apiKey = $this->getApiKey();
    }
    
    private function getApiKey() {
        return trim(file_get_contents('/home/master/api_keys/chatgpt_api_key.txt'));
    }
    
    /**
     * Fetches an associative array that gives both the sanitized response from the LLM and also
     * the amount of time it took to fetch the API response.
     */
    public function getResponseAndTime($prompt) {
        $response_and_time = []; 
        
        // Start Time
        $start_time = hrtime(true);
        
        // Get response here
        $response = $this->getResponse($prompt);
        $response_and_time["response"] = $response;
        
        // End Time
        $end_time = hrtime(true);

        // Calculate Execution time (in Nanoseconds)
        $execution_time = $end_time - $start_time;

        // Convert to Seconds (1 second = 1 billion nanoseconds)
        $execution_time_in_seconds = round($execution_time / 1e9, 2);
        $execution_time_in_seconds = strval($execution_time_in_seconds) . " Seconds";

        $response_and_time["time"] = $execution_time_in_seconds;
        
        return $response_and_time;
    }
    
    /**
     * Gets the API response given a prompt, has sanitization and error handling.
     */
    private function getResponse($prompt) {
        $response_data = $this->createMessage($prompt);
        
        // Check and return the response content.
        if (isset($response_data['choices'][0]['message']['content'])) {
            return $response_data['choices'][0]['message']['content'];
        } else {
            throw new \Exception('Unexpected response format: ' . print_r($response_data, true));
        }
    }
    
    private function sanitizeHtml($html) {
        // Define allowed HTML tags
        $allowedTags = '<p><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><br><div><span>';
        
        // Strip unwanted tags and attributes
        $sanitized = strip_tags($html, $allowedTags);
        
        return $sanitized;
    }

    /**
     * Function to send a prompt to the OpenAI API and return the response.
     *
     * @param string $prompt
     *   The query prompt to send to the API.
     *
     * @return string
     *   The response from the API.
     *
     * @throws \Exception
     *   Throws exception if there is an error in the cURL request or response.
     */
    function createMessage($prompt) {
        // The data you send to the API.
        $data = [
            'model' => $this->model,  // Specify the model you're using.
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 150,  // Adjust this and other parameters as needed.
            'temperature' => 0.7,
        ];

        // Initialize cURL session.
        $ch = curl_init();

        // Set cURL options.
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Execute cURL session and get the response.
        $response = curl_exec($ch);

        // Check for errors in cURL request.
        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error_msg);
        }

        // Close cURL session.
        curl_close($ch);

        // Decode the JSON response.
        return json_decode($response, true);

        // Check and return the response content.
        if (isset($response_data['choices'][0]['message']['content'])) {
            return $response_data['choices'][0]['message']['content'];
        } else {
            throw new \Exception('Unexpected response format: ' . print_r($response_data, true));
        }
    }
    
    
    
    
    
    
    
    
    
}