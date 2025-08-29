<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - TF-IDF Analyzer
 *
 * This file contains the code for the Chatbot Knowledge Navigator analysis.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_section_callback($args) {
    ?>
    <p>Use the 'Download Data' button to retrieve the Knowledge Navigator results.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Knowledge Navigator Analysis and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=knowledge-navigator&file=knowledge-navigator-analysis.md">here</a>.</b></p>
    <?php
    if (is_admin()) {
        $header = " ";
        $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_kn_analysis_download_csv')) . '">Download Data</a>';
        echo $header;
    }
}


// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_output_callback($args) {
    // Get the saved chatbot_chatgpt_kn_analysis_choice value or default to "CSV"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_kn_analysis_output', 'CSV'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', '$output_choice' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_kn_analysis_output" name="chatbot_chatgpt_kn_analysis_output">
        <option value="<?php echo esc_attr( 'CSV' ); ?>" <?php selected( $output_choice, 'CSV' ); ?>><?php echo esc_html( 'CSV' ); ?></option>
    </select>
    <?php
}


// Download the TF-IDF data
function chatbot_chatgpt_kn_analysis_download_csv() {

    global $chatbot_chatgpt_plugin_dir_path;

    // Generate the results directory path
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $results_dir_path);

    // Specify the output file's path
    $results_csv_file = $results_dir_path . 'results.csv';

    // Exit early if the file doesn't exist
    if (!file_exists($results_csv_file)) {
        // DIAG - Diagnostics - Ver 1.9.1
        // back_trace( 'NOTICE', 'File not found!');
        wp_die('File not found!');
    }

    // Read the file contents using WordPress functions
    $csv_data = file_get_contents($results_csv_file);

    // Check if reading the file was successful
    if ($csv_data === false) {
        wp_die('Error reading file.');
    }

    // Set headers for file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Knowledge Navigator Results.csv"');
    header('Content-Length: ' . filesize($results_csv_file));

    // Output the file contents
    readfile($results_csv_file);
    exit;

}
add_action('admin_post_chatbot_chatgpt_kn_analysis_download_csv', 'chatbot_chatgpt_kn_analysis_download_csv');
