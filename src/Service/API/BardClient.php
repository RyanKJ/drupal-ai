<?php
/**
 * Bard API Client with HTTP/2 support
 *
 * Suggested usage:
 * $client = new BardClient('your-api-key-here');
 * $response = $client->createMessage('Tell me a joke');
 * echo $response;
 */
 
namespace Drupal\drupalai\Service\API;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use \Exception;


/**
 * Class that calls and interfaces with Anthropic's Claude LLM
 */
class BardClient {
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
            $sanitizedData[$key] = sanitizeData($value);
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
     * Sends a message to the Gemini API.
     *
     * @param string $prompt
     *   The user prompt for the Gemini API.
     * @param string $apiUrl
     *   The API URL for the Gemini API.
     * @param string $apiKey
     *   The API key for the Gemini API.
     *
     * @return array|null
     *   Returns the API response data or null if the request fails.
     */
    public function createMessage(string $prompt) {
        $httpClient = new Client();

        $messageData = [
          'contents' => [
             [ 'parts' => [
                 ['text' => $prompt]
               ]
             ]
          ],
          'generationConfig' => [
            'model' => $model, // Add the model parameter
          ], 
        ];
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
             ], 
            'json' => $messageData,
        ];
        
        try {
            $response = $httpClient->request('POST', $this->generateApiUrl(), $options);
            $body = $response->getBody();
            $data = Json::decode($body, true);

          if($data){
            return $this->sanitizeData($data);
          } else {
            return ['error' => 'Failed to decode JSON from Gemini API.'];
          }
        } catch (RequestException $e) {
          \Drupal::logger('my_module')->error('Gemini API Request Error: @message', ['@message' => $e->getMessage()]);
            return ['error' => 'Gemini API request failed: ' . $e->getMessage()];
        }
    }
}


// Example usage:
try {
    $model = reset(BardClient::getModelOptions());
    
    $client = new BardClient($model);
    $response = $client->createMessage('Gemini, what is your take on the nature of consciousness?');
    
    echo $response; 
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
  