<?php
/**
 * Bard API Client with cURL (No Drupal JSON Serializer)
 *
 * Suggested usage:
 * $client = new BardClient('your-api-key-here');
 * $response = $client->createMessage('Tell me a joke');
 * echo $response;
 */

namespace Drupal\drupalai\Service\API;

use \Exception;

/**
 * Class that calls and interfaces with Anthropic's Claude LLM using cURL
 */
class BardClient
{
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1';

    private static $model_options = ['gemini-1.5-flash-8b' => 'Gemini 1.5 Flash',
                                     'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                                     'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash'];
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
        return trim(file_get_contents('/home/master/api_keys/gemini_api_key.txt'));
    }

    /**
     * Generate full URL for Gemini query.
     */
    private function generateApiUrl() {
        $full_url = $this->baseUrl . '/models/' . $this->model . ':generateContent';

        return $full_url;
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
     * Gets the API response given a prompt.
     */
    private function getResponse($prompt) {
        $response = $this->createMessage($prompt);

        return $response;
    }

    /**
     * Sanitizes data from the API response.
     *
     * @param array $data
     *   The data array to sanitize.
     *
     * @return array
     *   The sanitized data array.
     */
    private function sanitizeData(array $data) {
        $sanitizedData = [];
        // Loop through each key in the data array
        foreach ($data as $key => $value) {
            // Check if the value is an array
            if (is_array($value)) {
                // If it is an array, sanitize the values recursively
                $sanitizedData[$key] = $this->sanitizeData($value);
            } else {
                // If the value is not an array, apply the sanitation
                //Sanitize value against xss
                if(is_string($value)){
                    $sanitizedData[$key] = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                } else {
                    $sanitizedData[$key] = $value;
                }
            }
        }
        return $sanitizedData;
    }


    /**
     * Sends a message to the Gemini API using cURL.
     *
     * @param string $prompt
     *   The user prompt for the Gemini API.
     *
     * @return array|null
     *   Returns the API response data or null if the request fails.
     */
    public function createMessage(string $prompt) {
        $apiUrl = $this->generateApiUrl();

        $messageData = [
            'contents' => [
                [ 'parts' => [
                    ['text' => $prompt]
                    ]
                 ]
            ],
            'generationConfig' => [
              'model' => $this->model, // Add the model parameter
            ],
        ];

        $jsonData = json_encode($messageData);

        $curl = curl_init($apiUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $this->apiKey,
        ]);
         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // This line is not ideal for production, it should be removed

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
           \Drupal::logger('my_module')->error('Gemini API cURL Error: @message', ['@message' => $error]);
            return ['error' => 'Gemini API request failed: ' . $error];
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

       if ($httpCode >= 200 && $httpCode < 300) {
           $data = json_decode($response, true);
           if ($data) {
               return $this->sanitizeData($data);
           } else {
               return ['error' => 'Failed to decode JSON from Gemini API.'];
           }
       } else {
            return ['error' => 'Gemini API request failed with HTTP code: ' . $httpCode];
       }
    }
}


// Example usage:
try {
    $model = 'gemini-1.5-flash-8b';
    
    $client = new BardClient($model);
    $response = $client->createMessage('Gemini, what is your take on the nature of consciousness?');
    
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}