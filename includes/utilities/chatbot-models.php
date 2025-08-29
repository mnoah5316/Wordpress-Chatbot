<?php
/**
 * Kognetiks Chatbot - Chatbot Models
 *
 * This file contains the code to retrieve the list of available models
 * from OpenAI API and display them in the settings page.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Function to get the Model names from OpenAI API
function chatbot_openai_get_models() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    
    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'dall-e-3',
            'object' => 'model',
            'created' => 1698785189,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-3.5-turbo',
            'object' => 'model',
            'created' => 1677610602,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'tts-1-hd',
            'object' => 'model',
            'created' => 1699053241,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'whisper-1',
            'object' => 'model',
            'created' => 1677532384,
            'owned_by' => 'openai-internal'
        )
    );

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_chatgpt_model_choice')) === false) {
        update_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo');
    }
    if (esc_attr(get_option('chatbot_chatgpt_image_model_option')) === false) {
        update_option('chatbot_chatgpt_image_model_option', 'dall-e-3');
    }
    if (esc_attr(get_option('chatbot_chatgpt_voice_model_option')) === false) {
        update_option('chatbot_chatgpt_voice_model_option', 'tts-1-hd');
    }
    if (esc_attr(get_option('chatbot_chatgpt_whisper_model_option')) === false) {
        update_option('chatbot_chatgpt_whisper_model_option', 'whisper-1');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $openai_models_url = esc_attr(get_option('chatbot_chatgpt_base_url'));
    $openai_models_url = rtrim($openai_models_url, '/') . '/models';

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 15, // Set a timeout to avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($openai_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' . 'Error fetching OpenAI models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Decode the JSON response
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE' , '$models: ' . print_r($models, true));

    // Return the list of models
    return $models;

}

// Function to get the Model names from OpenAI API
function chatbot_azure_get_models() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    
    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_azure_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'dall-e-3',
            'object' => 'model',
            'created' => 1691712000,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-3.5-turbo',
            'object' => 'model',
            'created' => 1707955200,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o',
            'object' => 'model',
            'created' => 1715558400,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o-mini',
            'object' => 'model',
            'created' => 1721347200,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o-audio-preview',
            'object' => 'model',
            'created' => 1731369600,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'text-embedding-ada-002',
            'object' => 'model',
            'created' => 1680480000,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'whisper-1',
            'object' => 'model',
            'created' => 1677532384,
            'owned_by' => 'openai-internal'
        )
    );   

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_azure_model_choice')) === false) {
        update_option('chatbot_azure_model_choice', 'gpt-3.5-turbo');
    }
    if (esc_attr(get_option('chatbot_azure_image_model_option')) === false) {
        update_option('chatbot_azure_image_model_option', 'dall-e-3');
    }
    if (esc_attr(get_option('chatbot_azure_voice_model_option')) === false) {
        update_option('chatbot_azure_voice_model_option', 'tts-1-hd');
    }
    if (esc_attr(get_option('chatbot_azure_whisper_model_option')) === false) {
        update_option('chatbot_azure_whisper_model_option', 'whisper-1');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Assemble the URL from resource name and deployment name
    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    $azure_models_url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/models?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE' , 'chatbot_azure_get_models: ' . $azure_models_url);

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'api-key'      => trim($api_key),
            'Accept'       => 'application/json',
        ),
        'timeout' => 15, // Set a timeout to avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($azure_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' . 'Error fetching Azure OpenAI models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Decode the JSON response
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE' , 'chatbot_azure_get_models: ' . print_r($data, true));

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE' , '$models: ' . print_r($models, true));

    // Return the list of models
    return $models;

}

// Function to get the Model names from NVIDIA API
function chatbot_nvidia_get_models() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    
    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'nvidia/llama-3.1-nemotron-51b-instruct',
            'object' => 'model',
            'created' => 735790403,
            'owned_by' => 'nvidia'
        ),
    );

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_nvidia_model_choice')) === false) {
        update_option('chatbot_nvidia_model_choice', 'nvidia/llama-3_1-nemotron-70b-instruct');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $nvidia_models_url = esc_attr(get_option('chatbot_nvidia_base_url'));
    $nvidia_models_url = rtrim($nvidia_models_url, '/') . '/models';

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 15, // Avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($nvidia_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' , 'Error fetching NVIDIA models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);

    // Decode JSON response
    $data = json_decode($response_body, true);

    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        prod_trace( 'ERROR' , 'Invalid JSON response from NVIDIA API.');
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE' , '$models: ' . print_r($models, true));

    // Return the list of models
    return $models;

}

// Function to get the Model names from Anthropic API
function chatbot_anthropic_get_models() {

    // https://docs.anthropic.com/en/api/messages-examples
    // https://docs.anthropic.com/en/docs/models-overview
    // https://docs.anthropic.com/en/docs/about-claude/models

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'claude-3-5-sonnet-latest',
            'object' => 'model',
            'created' => 20241022,
            'owned_by' => 'anthropic'
        ),
        array(
            'id' => 'claude-3-5-haiku-latest',
            'object' => 'model',
            'created' => 20241022,
            'owned_by' => 'anthropic'
        ),
        array(
            'id' => 'claude-3-opus-latest',
            'object' => 'model',
            'created' => 20240229,
            'owned_by' => 'anthropic'
        ),
        array(
            'id' => 'claude-3-sonnet-20240229',
            'object' => 'model',
            'created' => 20240229,
            'owned_by' => 'anthropic'
        ),
        array(
            'id' => 'claude-3-haiku-20240307',
            'object' => 'model',
            'created' => 20240307,
            'owned_by' => 'anthropic'
        )
    );

    // FIXME - Anthropic API does not have an endpoint for models
    // Call the API to get the models

    // Decode the JSON response
    // $data = json_decode($response, true);

    // FIXME - Force an error since there is no api endpoint for models
    $data = array('error' => array('message' => 'No models endpoint available'));

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        ksum_prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE' , '$models: ' . print_r($models, true));

    // Return the list of models
    return $models;

}

// Function to get the Model names from DeepSeek API
function chatbot_deepseek_get_models() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE' , 'chatbot_deepseek_get_models');

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // https://api-docs.deepseek.com/
    // https://api-docs.deepseek.com/quick_start/pricing
    // https://api.deepseek.com/models

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'deepseek-chat',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'deepseek'
        ),
    );

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $deepseek_models_url = esc_attr(get_option('chatbot_deepseek_base_url'));
    $deepseek_models_url = rtrim($deepseek_models_url, '/') . '/models';

    // Set headers
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    // Perform the request
    $response = wp_remote_get($deepseek_models_url, $args);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));
    
    // Check for errors in the response
    if (is_wp_error($response)) {
        return $default_model_list;
    }

    // Decode the JSON response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if the response is valid and contains data
    if (isset($data['data']) && is_array($data['data'])) {
        $default_model_list = array_map(function($model) {
            return array(
                'id' => $model['id'],
                'object' => $model['object'],
                'created' => null, // Assuming 'created' is not provided in the response
                'owned_by' => $model['owned_by']
            );
        }, $data['data']);
    } else {
        // Handle the case where the response is not valid
        $default_model_list = array(
            array(
                'id' => 'deepseek-chat',
                'object' => 'model',
                'created' => null,
                'owned_by' => 'deepseek'
            ),
        );
    }

    // DeepSeek API does not have an endpoint for models
    return $default_model_list;

}

// Function to get the Model names from Mistral API
function chatbot_mistral_get_models() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE' , 'chatbot_mistral_get_models');

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'mistral-small-latest',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'mistral'
        ),
    );

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Set the API endpoint
    $mistral_models_url = esc_attr(get_option('chatbot_mistral_base_url'));
    $mistral_models_url = rtrim($mistral_models_url, '/') . '/models';

    // Set headers
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    // Perform the request
    $response = wp_remote_get($mistral_models_url, $args);

    // Check for errors in the response
    if (is_wp_error($response)) {
        return $default_model_list;
    }

    // Decode the JSON response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$data: ' . print_r($data, true));

    // Check if the response is valid and contains data
    if (isset($data['data']) && is_array($data['data'])) {
        $default_model_list = array_map(function($model) {
            return array(
                'id' => $model['id'],
                'object' => $model['object'],
                'created' => null,
                'owned_by' => $model['owned_by']
            );
        }, $data['data']);
    } else {
        // Handle the case where the response is not valid
        $default_model_list = array(
            array(
                'id' => 'mistral-small-latest',
                'object' => 'model',
                'created' => null,
                'owned_by' => 'mistral'
            ),
        );
    }

    // Mistral API does not have an endpoint for models
    return $default_model_list;

}
