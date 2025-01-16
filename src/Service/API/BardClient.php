<?php
/**
 * Bard API Client with cURL (No Drupal JSON Serializer)
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
     *
     * @param string $prompt
     *   The user prompt for the Gemini API.
     *
     * @return string|array
     *   Returns the API response text or an error array if the request fails.
     */
    private function getResponse($prompt) {
        $data = $this->createMessage($prompt);
        
        if(isset($data['error'])){
            return $data;
         } else {
            // Extract text from the 'candidates' array
            if (isset($data['candidates']) && is_array($data['candidates']) && count($data['candidates']) > 0) {
                $candidates = $data['candidates'];
                
                if(isset($candidates[0]['content']) && isset($candidates[0]['content']['parts']) && is_array($candidates[0]['content']['parts']) && count($candidates[0]['content']['parts']) > 0){
                    
                     $parts = $candidates[0]['content']['parts'];
                     
                     if (isset($parts[0]['text'])) {
                            return $parts[0]['text']; // Return the first 'text' element
                      } else {
                          return "Error: no 'text' found in response parts";
                      }
                
                } else {
                   return "Error: Could not find parts or content in candidates";
                }

             } else {
                 return "Error: Could not find candidates in response";
            }
        }
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
     * Fetches a list of available models using the Gemini API
     *
     * @return array|null
     *   Returns the API response data or null if the request fails.
     */
    public function listModels() {
         $apiUrl = $this->baseUrl . '/models';
         error_log("API URL: " . $apiUrl);
    
          $curl = curl_init($apiUrl);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HTTPHEADER, [
              'Content-Type: application/json',
              'x-goog-api-key: ' . $this->apiKey,
          ]);
           curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
           $response = curl_exec($curl);

           if ($response === false) {
               $error = curl_error($curl);
               curl_close($curl);
               error_log("cURL Error: " . $error);
               return ['error' => 'Gemini API request failed: ' . $error];
           }

           $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
           error_log("HTTP Code: " . $httpCode);
           curl_close($curl);
    
         if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                if ($data) {
                    return $this->sanitizeData($data);
                } else {
                    error_log("JSON Decode Error: Could not decode response " . $response);
                    return ['error' => 'Failed to decode JSON from Gemini API.'];
                }
         } else {
            error_log("Gemini API request failed with HTTP code: " . $httpCode . ", Response: " . $response);
            return ['error' => 'Gemini API request failed with HTTP code: ' . $httpCode];
        }
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
        error_log("API URL: " . $apiUrl);
    
         $messageData = [
            'contents' => [
                [ 'parts' => [
                    ['text' => $prompt]
                    ]
                 ]
            ],
         ];
    
        error_log("Message Data (Pre-JSON): " . print_r($messageData, true));
        $jsonData = json_encode($messageData);
        error_log("JSON Data: " . $jsonData);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            error_log("JSON Encode Error: " . $error);
            return ['error' => 'JSON Encoding Error: ' . $error];
        }
    
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
            error_log("cURL Error: " . $error);
            return ['error' => 'Gemini API request failed: ' . $error];
        }
        
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        error_log("HTTP Code: " . $httpCode);
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            if ($data) {
                return $this->sanitizeData($data);
            } else {
                error_log("JSON Decode Error: Could not decode response " . $response);
                return ['error' => 'Failed to decode JSON from Gemini API.'];
            }
        } else {
            error_log("Gemini API request failed with HTTP code: " . $httpCode . ", Response: " . $response);
            return ['error' => 'Gemini API request failed with HTTP code: ' . $httpCode];
        }
    }
}


// Example usage:
try {
    $model = 'gemini-1.5-flash-8b';
    $client = new BardClient($model);
    
    // List Models
    $models = $client->listModels();
    echo "Available Models:\n";
    echo '<pre>';
    print_r($models);
    echo '</pre>';
    
   // Attempt query
    $model = 'gemini-2.0-flash-exp';
    $client = new BardClient($model);
    $response = $client->getResponse('Gemini, what is your favorite color?');
    
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}