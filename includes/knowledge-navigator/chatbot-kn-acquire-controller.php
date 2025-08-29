<?php
/**
 * Kognetiks Chatbot - Settings - Knowledge Navigator - Acquire Content Awareness
 *
 * This file contains the code for the Chatbot Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Batch the acquisition of site content
//
// This process is intended to scale to large sites with many pages, posts and products.
//
// Start with the first batch and acquire the content for each published post, page, or product
// The results in the chatbot_chatgpt_knowledge_base table.
//
// This process is run in the background using the WordPress cron system.
//
// The frequency of the batch acquisition can be set in the Chatbot Knowledge Navigator settings.
//
// The knowledge acquisition is run in multiple steps:
// 1. Initialize - Initialize the batch acquisition for posts, pages, and products
// 2. Run - Acquires the content for each post, page, or product in the batch
// 3. Reinitialize - Reinitialize the batch acquisition for comments
// 4. Run - Acquires the content for each comment in the batch
// 5. Analyze - Analyze the acquired content
//
// The knowledge acquisition can be cancelled at any time.
//
// The knowledge acquisition is completed when all published pages, posts and products have been analyzed.
//
// The batch acquisition can be run manually by clicking the setting the "Select Run Schedule" to "Now"
// in the Chatbot Knowledge Navigator settings.
//
// The batch acquisition can be cancelled manually by clicking the setting the "Select Run Schedule" to
// one of "Now", "Hourly", "Twice Daily", "Daily" or "Weekly" in the Chatbot Knowledge Navigator settings.
//

// Chatbot Knowledge Navigator - Controller
function chatbot_kn_acquire_controller() {

    // Get the current action
    $action = esc_attr( get_option( 'chatbot_chatgpt_kn_action', 'initialize' ) ); // Default to run to kick off the process

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

    switch ( $action ) {
        case 'initialize':
            // Initialize the knowledge acquisition process
            chatbot_kn_initialization();
            break;
        case 'phase 1':
            chatbot_kn_run_phase_1();
            break;
        case 'phase 2':
            // Reinitialize the batch acquisition for comments
            chatbot_kn_reinitialization();
            break;
        case 'phase 3':
            chatbot_kn_run_phase_3();
            break;
        case 'phase 4':
            // Determine the top words and word pairs
            chatbot_kn_run_phase_4();
            break;
        case 'phase 5':
            // Reinitialize the batch acquisition for pages, posts, and products
            chatbot_kn_run_phase_5();
            break;
        case 'phase 6':
            chatbot_kn_run_phase_6();
            break;
        case 'phase TBD':
            // Assign scores to the top 10% of the words in comments
            // chatbot_kn_run_phase_TBD();
            break;
        case 'phase 7':
            chatbot_kn_output_the_results();
            break;    
        case 'phase 8':
            // Wrap up the knowledge acquisition process
            chatbot_kn_wrap_up();
            update_option( 'chatbot_chatgpt_kn_action', 'completed' );
            break;            
        case 'completed':
            return;
        case 'cancel':
            // chatbot_kn_cancel_batch_acquisition();
            break;
        default:
            break;
    }

}
// Add the action hook
add_action( 'chatbot_kn_acquire_controller', 'chatbot_kn_acquire_controller' );

// Initialize the knowledge acquisition process
function chatbot_kn_initialization() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_phase_1_initialization' );

    // Since this is the first step, set the item count = 0
    update_option( 'chatbot_chatgpt_kn_item_count', 0 );

    // Define the batch size
    // FIXME - This should be set in the settings and default to 50
    update_option('chatbot_chatgpt_kn_items_per_batch', 50); // Fetching 50 items at a time

    // Reset the chatbot_chatgpt_knowledge_base table
    dbKNStore();

    // Reset the chatbot_chatgpt_knowledge_base_word_count table
    dbKNStoreWordCount();

    // Reset the chatbot_chatgpt_knowledge_base_tfidf table
    dbKNStoreTFIDF();

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Reset chatbot_chatgpt_kn_total_word_count to 0
    update_option('chatbot_chatgpt_kn_total_word_count', 0);

    // Reset chatbot_chatgpt_kn_document_count to 0
    update_option('chatbot_chatgpt_kn_document_count', 0);

    // Reset the number of items analyzed
    update_option('chatbot_chatgpt_no_of_items_analyzed', 0);

    // Get teh number of posts, pages, products and comments
    chatbot_kn_count_documents();

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

}

function chatbot_kn_reinitialization() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_phase_2_initialization' );

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    update_option('chatbot_chatgpt_kn_item_count', 0);

    update_option('chatbot_chatgpt_kn_action', 'phase 3');

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

}

// Count the number of posts, pages, and products
function chatbot_kn_count_documents() {
    
    global $wpdb;
    $document_count = 0;

    // Get all post types that exist in the database
    $db_post_types = $wpdb->get_col(
        "SELECT DISTINCT post_type FROM {$wpdb->prefix}posts 
        WHERE post_type NOT LIKE 'wp_%' 
        AND post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset')"
    );

    // Get all registered public post types
    $registered_types = get_post_types(['public' => true], 'objects');
    
    // Initialize post_types array
    $post_types = [];
    
    // First, process registered types
    foreach ($registered_types as $type) {
        $plural_type = $type->name === 'reference' ? 'references' : $type->name . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
            $post_types[] = $type->name;
        }
    }
    
    // Then, process any additional types found in the database
    foreach ($db_post_types as $type) {
        if (!in_array($type, $post_types)) { // Only process if not already included
            $plural_type = $type === 'reference' ? 'references' : $type . 's';
            $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
            if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
                $post_types[] = $type;
            }
        }
    }

    // Count comments separately since they're not a post type
    if (esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'No')) === 'Yes') {
        $comment_count = $wpdb->get_var(
            "SELECT COUNT(comment_post_ID) FROM {$wpdb->prefix}comments WHERE comment_approved = '1'"
        );
        $document_count += $comment_count;
    }

    // Update the total number of documents
    update_option('chatbot_chatgpt_kn_document_count', $document_count);

}

// Acquire the content for each page, post, or product in the run
function chatbot_kn_run_phase_1() {

    global $wpdb;

    // Get the item count
    $offset = esc_attr(get_option('chatbot_chatgpt_kn_item_count', 0));
    $batch_size = esc_attr(get_option('chatbot_chatgpt_kn_items_per_batch', 50));
    $chatbot_chatgpt_no_of_items_analyzed = esc_attr(get_option('chatbot_chatgpt_no_of_items_analyzed', 0));

    // Set the next starting point
    update_option('chatbot_chatgpt_kn_item_count', $offset + $batch_size);

    // Get all post types that exist in the database
    $db_post_types = $wpdb->get_col(
        "SELECT DISTINCT post_type FROM {$wpdb->prefix}posts 
        WHERE post_type NOT LIKE 'wp_%' 
        AND post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset')"
    );

    // Get all registered public post types
    $registered_types = get_post_types(['public' => true], 'objects');
    
    // Initialize post_types array
    $post_types = [];
    
    // First, process registered types
    foreach ($registered_types as $type) {
        $plural_type = $type->name === 'reference' ? 'references' : $type->name . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
            $post_types[] = $type->name;
        }
    }
    
    // Then, process any additional types found in the database
    foreach ($db_post_types as $type) {
        if (!in_array($type, $post_types)) { // Only process if not already included
            $plural_type = $type === 'reference' ? 'references' : $type . 's';
            $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
            if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
                $post_types[] = $type;
            }
        }
    }

    // List the post types
    // back_trace( 'NOTICE', 'Post types: ' . print_r($post_types, true) );

    // If no post types are selected, move to phase 2
    if (empty($post_types)) {
        update_option('chatbot_chatgpt_kn_action', 'phase 2');
        wp_schedule_single_event(time() + 30, 'chatbot_kn_acquire_controller');
        return;
    }

    // Prepare the SQL query part for post types
    $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
    $prepared_query = $wpdb->prepare(
        "SELECT ID, post_title, post_content, post_excerpt, post_type FROM {$wpdb->prefix}posts 
        WHERE post_type IN ($placeholders) AND post_status = 'publish' 
        ORDER BY ID ASC LIMIT %d OFFSET %d",
        array_merge($post_types, [$batch_size, $offset])
    );

    // Get the published items
    $results = $wpdb->get_results($prepared_query);

    // Handle any database errors
    if (is_wp_error($results)) {
        prod_trace('ERROR', 'Database error: ' . $results->get_error_message());
        return;
    }

    // If no more results, move to phase 2
    if (empty($results)) {
        update_option('chatbot_chatgpt_kn_action', 'phase 2');
        wp_schedule_single_event(time() + 30, 'chatbot_kn_acquire_controller');
        return;
    }

    // Process the results
    foreach ($results as $result) {
        $Content = $result->post_content;

        if (!empty($Content)) {
            $ContentUtf8 = $Content;
            kn_acquire_words($ContentUtf8, 'add');
        }

        $chatbot_chatgpt_no_of_items_analyzed++;
    }

    // Update the number of items analyzed
    update_option('chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed);
    update_option('chatbot_chatgpt_kn_action', 'phase 1');

    // Schedule the next action
    wp_schedule_single_event(time() + 30, 'chatbot_kn_acquire_controller');

    // Unset large variables to free memory
    unset($results);
}

// Acquire the content for each comment in the run
function chatbot_kn_run_phase_3() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_3' );

    // Get the item count
    $offset = esc_attr(get_option('chatbot_chatgpt_kn_item_count', 0)); // Default offset set to 0 if not specified
    // FIXME - This should be set in the settings and default to 50
    $batch_size = esc_attr(get_option('chatbot_chatgpt_kn_items_per_batch', 50)); // Fetching 50 items at a time
    $chatbot_chatgpt_no_of_items_analyzed = esc_attr(get_option('chatbot_chatgpt_no_of_items_analyzed', 0));

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$offset: ' . $offset );
    // back_trace( 'NOTICE', '$batch_size: ' . $batch_size );
    // back_trace( 'NOTICE', '$chatbot_chatgpt_no_of_items_analyzed: ' . $chatbot_chatgpt_no_of_items_analyzed );

    // Set the next starting point
    update_option( 'chatbot_chatgpt_kn_item_count', $offset + $batch_size );

    // Get the setting for including comments
    $chatbot_chatgpt_kn_include_comments = esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'No'));

    // Query WordPress database for comment content
    if ($chatbot_chatgpt_kn_include_comments === 'Yes') {

        // Prepare the SQL query for fetching approved comments
        $prepared_query = $wpdb->prepare(
            "SELECT comment_ID, comment_post_ID, comment_content FROM {$wpdb->prefix}comments WHERE comment_approved = '1' 
            ORDER BY comment_ID ASC LIMIT %d OFFSET %d",
            array_merge([$batch_size, $offset])
        );
    
        // Execute the query and fetch results
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$prepared_query: ' . $prepared_query);

    } else {

        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', 'Exclude comments');

        unset($results);

        update_option( 'chatbot_chatgpt_kn_action', 'phase 4' );
        // Schedule the next action
        wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

        return;
    }

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', 'No more items to process' );
        update_option( 'chatbot_chatgpt_kn_action', 'phase 4' );
        // Schedule the next action
        wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );
        return;
    }

    // Process the results

    // Loop through query results
    foreach ($results as $result) {

        // DIAG - Diagnostics - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     // back_trace( 'NOTICE', 'Key: ' . $key . ' Value: ' . $value);
        // }        

        // Directly use the post content
        if (array_key_exists('comment_content', $result)) {
            $commentContent = $result['comment_content'];
        } else {
            // Handle the case where the key does not exist
            $commentContent = "";
            // DIAG - Diagnostics - Ver 1.9.6
            // back_trace( 'NOTICE', 'Comment has empty content.');
            continue;
        }
       
    // Check if the comment content is not empty
    if (!empty($commentContent)) {
        // Check if the content is already UTF-8
        if (mb_detect_encoding($commentContent, 'UTF-8', true) !== 'UTF-8') {
            // Convert to UTF-8 only if it's not already UTF-8
            $commentContentUtf8 = mb_convert_encoding($commentContent, 'UTF-8', 'auto');
        } else {
            // Content is already UTF-8
            $commentContentUtf8 = $commentContent;
        }

        // Pass UTF-8 content to the function
        kn_acquire_words($commentContentUtf8, 'add');
    } else {
        // Handle the case where content is empty
        continue;
    }

        // Increment the number of items analyzed by one
        $chatbot_chatgpt_no_of_items_analyzed++;
    
    }

    // Update the number of items analyzed
    update_option('chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 3' );

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);

}

// Phase 4 - Compute the TF-IDF
function chatbot_kn_run_phase_4() {
    global $wpdb;

    // Maximum number of top words
    $max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100));
    
    // Get total document count and word count
    $totalDocumentCount = esc_attr(get_option('chatbot_chatgpt_kn_document_count', 0));
    $totalWordCount = esc_attr(get_option('chatbot_chatgpt_kn_total_word_count', 0));

    // Debug log the values
    // back_trace( 'NOTICE', 'TF-IDF Calculation - Total Documents: ' . $totalDocumentCount . ', Total Words: ' . $totalWordCount);

    if ($totalDocumentCount == 0 || $totalWordCount == 0) {
       // back_trace( 'ERROR', 'Zero total documents or words found');
        return;
    }
    
    // SQL query to fetch top words based on their document count
    $results = $wpdb->get_results(
        "SELECT word, word_count, document_count FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count 
        ORDER BY document_count DESC LIMIT $max_top_words"
    );

    // Debug log the first few results
    // back_trace( 'NOTICE', 'First few words found: ' . print_r(array_slice($results, 0, 5), true));
    
    foreach ($results as $result) {
        $word = $result->word;
        $wordCount = $result->word_count;
        $documentCount = $result->document_count;

        // Calculate Term Frequency (TF)
        // TF = number of times term appears in document / total number of terms in document
        $tf = $wordCount / $totalWordCount;

        // Calculate Inverse Document Frequency (IDF)
        // IDF = log(total number of documents / number of documents containing term)
        $idf = log($totalDocumentCount / $documentCount);

        // Calculate TF-IDF
        $tfidf = $tf * $idf;

        // Debug log the calculations
        // back_trace( 'NOTICE', "Word: $word, TF: $tf, IDF: $idf, TF-IDF: $tfidf");

        // Store the TF-IDF in the chatbot_chatgpt_knowledge_base_tfidf table
        $wpdb->insert(
            $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf',
            array(
                'word' => $word,
                'score' => $tfidf
            ),
            array('%s', '%f')
        );
    }
    
    // Unset large variables to free memory
    unset($results);

    update_option('chatbot_chatgpt_kn_action', 'phase 5');
    wp_schedule_single_event(time() + 30, 'chatbot_kn_acquire_controller');
}

// Phase 5 - Reinitialize the batch acquisition for pages, posts, and products
function chatbot_kn_run_phase_5() {

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_5' );

    // REINITIALIZE THE BATCH ACQUISITION

    // Since this is the first step, set the item count = 0
    update_option( 'chatbot_chatgpt_kn_item_count', 0 );

    // Define the batch size
    // FIXME - This should be set in the settings and default to 50
    update_option('chatbot_chatgpt_kn_items_per_batch', 50); // Fetching 50 items at a time

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 6' );

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

}

// Phase 6 - Assign scores to the top 10% of the words in pages, posts, and products
function chatbot_kn_run_phase_6() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_5' );

    // Get the item count
    $offset = esc_attr(get_option('chatbot_chatgpt_kn_item_count', 0));
    $batch_size = esc_attr(get_option('chatbot_chatgpt_kn_items_per_batch', 50));
    $chatbot_chatgpt_no_of_items_analyzed = esc_attr(get_option('chatbot_chatgpt_no_of_items_analyzed', 0));

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$offset: ' . $offset );
    // back_trace( 'NOTICE', '$batch_size: ' . $batch_size );
    // back_trace( 'NOTICE', '$chatbot_chatgpt_no_of_items_analyzed: ' . $chatbot_chatgpt_no_of_items_analyzed );

    // Set the next starting point
    update_option( 'chatbot_chatgpt_kn_item_count', $offset + $batch_size );

    // Get all post types that exist in the database
    $db_post_types = $wpdb->get_col(
        "SELECT DISTINCT post_type FROM {$wpdb->prefix}posts 
        WHERE post_type NOT LIKE 'wp_%' 
        AND post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset')"
    );

    // Get all registered public post types
    $registered_types = get_post_types(['public' => true], 'objects');
    
    // Initialize post_types array
    $post_types = [];
    
    // First, process registered types
    foreach ($registered_types as $type) {
        $plural_type = $type->name === 'reference' ? 'references' : $type->name . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
            $post_types[] = $type->name;
        }
    }
    
    // Then, process any additional types found in the database
    foreach ($db_post_types as $type) {
        if (!in_array($type, $post_types)) { // Only process if not already included
            $plural_type = $type === 'reference' ? 'references' : $type . 's';
            $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
            if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
                $post_types[] = $type;
            }
        }
    }

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Post types: ' . print_r($post_types, true));

    // If no post types are selected, move to phase 7
    if (empty($post_types)) {
        update_option('chatbot_chatgpt_kn_action', 'phase 7');
        wp_schedule_single_event(time() + 30, 'chatbot_kn_acquire_controller');
        return;
    }

    // Prepare the SQL query part for post types
    $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
    $prepared_query = $wpdb->prepare(
        "SELECT ID, post_title, post_content, post_excerpt, post_type FROM {$wpdb->prefix}posts 
        WHERE post_type IN ($placeholders) AND post_status = 'publish' 
        ORDER BY ID ASC LIMIT %d OFFSET %d",
        array_merge($post_types, [$batch_size, $offset])
    );

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$prepared_query: ' . $prepared_query );

    // Get the published items
    $results = $wpdb->get_results($prepared_query);

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', 'No more items to process' );
        update_option( 'chatbot_chatgpt_kn_action', 'phase 7' );

        // Schedule the next action
        wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );
        return;
    }

    // Process the results

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostics - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     // back_trace( 'NOTICE', 'Key: ' . $key . ' Value: ' . $value);
        // }        

        // Directly use the post content
        $Content = $result->post_content;


        // Check if the post content is not empty
        if (!empty($Content)) {
            // Check if the content is already UTF-8
            if (mb_detect_encoding($Content, 'UTF-8', true) !== 'UTF-8') {
                // Convert to UTF-8 only if it's not already UTF-8
                $ContentUtf8 = mb_convert_encoding($Content, 'UTF-8', 'auto');
            } else {
                // Content is already UTF-8
                $ContentUtf8 = $Content;
            }
    
            // Now call kn_acquire_words with the UTF-8 encoded content
            $words = kn_acquire_words( $ContentUtf8 , 'skip');

            $wordScores = array();

            // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table if the word is found in the TF-IDF table
            foreach ( $words as $word ) {
                $tfidf = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf WHERE word = %s",
                        $word
                    )
                );
            
                $wordScores[$word] = $tfidf;
            }

            // Sort the $words array by $tfidf in descending order
            arsort($wordScores);

            // Count the number of words in the $words array
            $word_count = count($wordScores);

            // Get the tuning percentage from the options table - Stored as an integer between 0 and 100, so divide by 100
            $tuning_percentage = esc_attr(get_option('chatbot_chatgpt_kn_tuning_percentage', 25)) / 100;

            // Trim the $words array to the top 10% of the words
            // FIXME - This should be set in the settings and default to 10%
            $top_words = array_slice( $wordScores, 0, ceil($word_count * $tuning_percentage ), true);

            // Store the top words in the chatbot_chatgpt_knowledge_base table
            foreach ($top_words as $word => $score) {
                // Construct the URL for the post
                $url = get_permalink($result->ID);
                
                // Construct the Title for the post
                $title = get_the_title($result->ID);

                // Check if score is not null
                if ($score !== null) {
                    // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table
                    $wpdb->insert(
                        $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                        array(
                            'url' => $url,
                            'title' => $title,
                            'word' => $word,
                            'score' => $score,
                            'pid' => $result->ID
                        )
                    );
                }
            }
        } else {
            // Handle the case where content is empty
            continue;
        }

        // Increment the number of items analyzed by one
        // $chatbot_chatgpt_no_of_items_analyzed++;
    
    }

    // Update the number of items analyzed
    // update_option('chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 6' );

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);

}

// Output the results
function chatbot_kn_output_the_results() {

    global $chatbot_chatgpt_plugin_dir_path;

    global $wpdb;

    // Generate directory path
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $results_dir_path);

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($results_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace( 'ERROR', 'Failed to create directory.');
        return;
    }

    // DIAG - Log directory path for debugging
    // back_trace( 'NOTICE', 'Directory path: ' . $results_dir_path);

    // Remove legacy files
    if (file_exists($results_dir_path . 'results-comments.log')) {
        unlink($results_dir_path . 'results-comments.log');
    }
    if (file_exists($results_dir_path . 'results-pages.log')) {
        unlink($results_dir_path . 'results-pages.log');
    }
    if (file_exists($results_dir_path . 'results-posts.log')) {
        unlink($results_dir_path . 'results-posts.log');
    }

    // Prepare CSV file for output
    $results_csv_file = $results_dir_path . 'results.csv';
    // back_trace( 'NOTICE', 'CSV file for output: ' . $results_csv_file);

    // Delete CSV file if it already exists
    if (file_exists($results_csv_file)) {
        unlink($results_csv_file);
    }

    // Prepare JSON file for output
    $results_json_file = $results_dir_path . 'results.json';
    // back_trace( 'NOTICE', 'JSON file: ' . $results_json_file);

    // Delete JSON file if it already exists
    if (file_exists($results_json_file)) {
        unlink($results_json_file);
    }

    // Retrieve the list of words and the score for each word ordered by score descending in the TF-IDF table
    $results = $wpdb->get_results(
        "SELECT word, score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf ORDER BY score DESC"
    );

    // Write CSV for pages, posts, and products
    try {
        $f = new SplFileObject($results_csv_file, 'w');
        $f->fputcsv(['Word', 'TF-IDF']);
        foreach ($results as $result) {
            $f->fputcsv([$result->word, $result->score]);
        }
    } catch (RuntimeException $e) {
        // back_trace( 'ERROR', 'Failed to open CSV file for writing: ' . $e->getMessage());
    }

    // Write JSON for pages, posts, and products
    try {
        if (file_put_contents($results_json_file, json_encode($results)) === false) {
            throw new Exception("Failed to write to JSON file.");
        }
    } catch (Exception $e) {
        // back_trace( 'ERROR', $e->getMessage());
    }

    // Close the files
    $f = null;

    // Retrieve the list of words and the score for each word ordered by score descending in the TF-IDF table
    $results = $wpdb->get_results(
        "SELECT word, score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf ORDER BY score DESC"
    );

    // Store the top words for context
    $chatbot_chatgpt_kn_conversation_context = "This site includes references to and information about the following topics: ";

    foreach ($results as $result) {
        $chatbot_chatgpt_kn_conversation_context .= $result->word . ", ";
    }
    
    $chatbot_chatgpt_kn_conversation_context .= "and more.";

    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_conversation_context: ' . $chatbot_chatgpt_kn_conversation_context);
    
    // Save the results in the option for later use
    update_option('chatbot_chatgpt_kn_conversation_context', $chatbot_chatgpt_kn_conversation_context);

    // Unset large variables to free memory
    unset($results);

    // // Now write the .log files
    // $tfidf_results = $results_dir_path . 'tfidf_results.csv';
    // back_trace( 'NOTICE', 'Log file: ' . $tfidf_results);

    // // Delete log file if it already exists
    // if (file_exists($tfidf_results)) {
    //     unlink($tfidf_results);
    // }

    // // Retrieve the words and the scores for each URL in the knowledge base table
    // $results = $wpdb->get_results(
    //     "SELECT id, url, title, word, score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base ORDER BY title, url, score DESC"
    // );

    // // Write the log file
    // try {
    //     $f = new SplFileObject($tfidf_results, 'w');
    //     $f->fputcsv(['ID', 'URL', 'Title', 'Word', 'Score']);
    //     foreach ($results as $result) {
    //         $f->fputcsv([$result->id, $result->url, $result->title, $result->word, $result->score]);
    //     }
    // } catch (RuntimeException $e) {
    //     // back_trace( 'ERROR', 'Failed to open log file for writing: ' . $e->getMessage());
    // }

    // // Close the file
    // $f = null;

    // // Unset large variables to free memory
    // unset($results);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 8' );

    // Schedule the next action
    wp_schedule_single_event( time() + 30, 'chatbot_kn_acquire_controller' );

}

// Wrap up the knowledge acquisition process
function chatbot_kn_wrap_up() {

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_wrap_up' );

    // FIXME - Drop the chatbot_chatgpt_knowledge_base_word_count table
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'Dropping chatbot_chatgpt_knowledge_base_word_count table' );
    dbKNClean();

    // Save the results message value into the option
    $kn_results = 'Knowledge Navigator completed! Visit the plugin settings Analysis tab to download the results.';
    update_option('chatbot_chatgpt_kn_results', $kn_results);

    // Notify outcome for up to 3 minutes
    set_transient('chatbot_chatgpt_kn_results', $kn_results);

    // Get the current date and time.
    $date_time_completed = date("Y-m-d H:i:s");

    // Concatenate the status message with the date and time.
    $status_message = 'Completed on ' . $date_time_completed;

    // Update the option with the new status message.
    update_option('chatbot_chatgpt_kn_status', $status_message);

}
