<?php
/**
 * Vanilla PHP Amazon Bedrock Client
 * Makes direct HTTP calls to Bedrock API using API Key authentication
 * No AWS SDK required
 */

class BedrockClient {
    private $api_key;
    private $region;
    private $model_id;
    
    public function __construct($api_key, $region = 'us-east-1', $model_id = null) {
        $this->api_key = $api_key;
        $this->region = $region;
        $this->model_id = $model_id ?? BEDROCK_MODEL_ID;
    }
    
    /**
     * Call Bedrock Converse API
     * 
     * @param string $prompt The user prompt
     * @param array $options Optional parameters (maxTokens, temperature, etc.)
     * @return string|null Response text or null on error
     */
    public function converse($prompt, $options = []) {
        $endpoint = "https://bedrock-runtime.{$this->region}.amazonaws.com/model/{$this->model_id}/converse";
        
        // Build request payload
        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'inferenceConfig' => [
                'maxTokens' => $options['maxTokens'] ?? BEDROCK_MAX_TOKENS,
                'temperature' => $options['temperature'] ?? 0.7
            ]
        ];
        
        // Add system prompt if provided
        if (isset($options['system'])) {
            $payload['system'] = [
                ['text' => $options['system']]
            ];
        }
        
        $json_payload = json_encode($payload);
        
        // Make HTTP request with API key
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // Set headers with API key authentication
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Handle errors
        if ($curl_error) {
            $error_msg = "Bedrock cURL error: " . $curl_error;
            error_log($error_msg);
            if (function_exists('log_error')) {
                log_error($error_msg);
            }
            return null;
        }
        
        if ($http_code !== 200) {
            $error_msg = "Bedrock API error (HTTP $http_code): " . substr($response, 0, 500);
            error_log($error_msg);
            if (function_exists('log_error')) {
                log_error($error_msg);
            }
            return null;
        }
        
        // Parse response
        $result = json_decode($response, true);
        
        if (isset($result['output']['message']['content'][0]['text'])) {
            return $result['output']['message']['content'][0]['text'];
        }
        
        // Log the unexpected format for debugging
        $error_msg = "Unexpected Bedrock response format. Response: " . substr($response, 0, 500);
        error_log($error_msg);
        if (function_exists('log_error')) {
            log_error($error_msg);
        }
        return null;
    }
    
    /**
     * Invoke model (legacy method for compatibility)
     */
    public function invokeModel($prompt, $maxTokens = null) {
        return $this->converse($prompt, [
            'maxTokens' => $maxTokens ?? BEDROCK_MAX_TOKENS
        ]);
    }
}

/**
 * Helper function to get Bedrock client instance
 */
function get_bedrock_client() {
    static $client = null;
    if ($client === null) {
        $client = new BedrockClient(
            BEDROCK_API_KEY,
            AWS_REGION,
            BEDROCK_MODEL_ID
        );
    }
    return $client;
}
