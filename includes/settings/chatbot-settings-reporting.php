<?php
/**
 * Kognetiks Chatbot - Settings - Reporting
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the reporting settings and other parameters.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Reporting settings - Ver 2.0.7
function chatbot_chatgpt_reporting_settings_init() {

    // Register settings for Reporting
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_reporting_period');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_enable_conversation_logging');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_log_days_to_keep');

    // Reporting Overview Section
    add_settings_section(
        'chatbot_chatgpt_reporting_overview_section',
        'Reporting Overview',
        'chatbot_chatgpt_reporting_overview_section_callback',
        'chatbot_chatgpt_reporting_overview'
    );

    // Reporting Settings Section
    add_settings_section(
        'chatbot_chatgpt_reporting_section',
        'Reporting Settings',
        'chatbot_chatgpt_reporting_section_callback',
        'chatbot_chatgpt_reporting'
    );

    // Reporting Settings Field - Reporting Period
    add_settings_field(
        'chatbot_chatgpt_reporting_period',
        'Reporting Period',
        'chatbot_chatgpt_reporting_period_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Enable Conversation Logging
    add_settings_field(
        'chatbot_chatgpt_enable_conversation_logging',
        'Enable Conversation Logging',
        'chatbot_chatgpt_enable_conversation_logging_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Conversation Log Days to Keep
    add_settings_field(
        'chatbot_chatgpt_conversation_log_days_to_keep',
        'Conversation Log Days to Keep',
        'chatbot_chatgpt_conversation_log_days_to_keep_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Conversation Data Section
    add_settings_section(
        'chatbot_chatgpt_conversation_reporting_section',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_section_callback',
        'chatbot_chatgpt_conversation_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_conversation_reporting_field',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_conversation_reporting_section'
    );

    // Interaction Data Section
    add_settings_section(
        'chatbot_chatgpt_interaction_reporting_section',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_section_callback',
        'chatbot_chatgpt_interaction_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_interaction_reporting_field',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_interaction_reporting_section'
    );

    // // Token Data Section
    add_settings_section(
        'chatbot_chatgpt_token_reporting_section',
        'Token Data',
        'chatbot_chatgpt_token_reporting_section_callback',
        'chatbot_chatgpt_token_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_token_reporting_field',
        'Token Data',
        'chatbot_chatgpt_token_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_token_reporting_section'
    );
   
}
add_action('admin_init', 'chatbot_chatgpt_reporting_settings_init');

// Reporting section callback - Ver 1.6.3
function chatbot_chatgpt_reporting_overview_section_callback($args) {
    ?>
    <div>
        <p>Use these setting to select the reporting period for Visitor and User Interactions.</p>
        <p>Please review the section <b>Conversation Logging Overview</b> on the <a href="?page=chatbot-chatgpt&tab=support&dir=support&file=conversation-logging-and-history.md">Support</a> tab of this plugin for more details.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Reporting and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=reporting&file=reporting.md">here</a>.</b></p>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_section_callback($args) {
    ?>
    <div>
        <p>Use these settings to select the reporting period for Visitor and User Interactions.</p>
        <p>You will need to Enable Conversation Logging if you want to record chatbot interactions. By default, conversation logging is initially turned <b>Off</b>.</p>
        <p>Conversation Log Days to Keep sets the number of days to keep the conversation log data in the database.</p>
    </div>
    <?php
}

function chatbot_chatgpt_conversation_reporting_section_callback($args) {
    ?>
    <div>
        <p>Conversation items stored in your DB total <b><?php echo chatbot_chatgpt_count_conversations(); ?></b> rows (includes both Visitor and User input and chatbot responses).</p>
        <p>Conversation items stored take up <b><?php echo chatbot_chatgpt_size_conversations(); ?> MB</b> in your database.</p>
        <p>Use the button (below) to retrieve the conversation data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_conversation_data')) . '">Download Conversation Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_interaction_reporting_section_callback($args) {
    ?>
    <div>
        <!-- TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8 -->
        <!-- <p><?php echo do_shortcode('[chatbot_simple_chart from_database="true"]'); ?></p> -->
        <p><?php echo chatbot_chatgpt_interactions_table() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_interactions_data')) . '">Download Interaction Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_token_reporting_section_callback($args) {
    ?>
    <div>
        <p><?php echo chatbot_chatgpt_total_tokens() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_token_usage_data')) . '">Download Token Usage Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_settings_callback($args){
    ?>
    <div>
        <h3>Reporting Settings</h3>
    </div>
    <?php
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_reporting_period_callback($args) {
    // Get the saved chatbot_chatgpt_reporting_period value or default to "Daily"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_reporting_period', 'Daily'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_reporting_period' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_reporting_period" name="chatbot_chatgpt_reporting_period">
        <option value="<?php echo esc_attr( 'Daily' ); ?>" <?php selected( $output_choice, 'Daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
        <!-- <option value="<?php echo esc_attr( 'Weekly' ); ?>" <?php selected( $output_choice, 'Weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option> -->
        <option value="<?php echo esc_attr( 'Monthly' ); ?>" <?php selected( $output_choice, 'Monthly' ); ?>><?php echo esc_html( 'Monthly' ); ?></option>
        <option value="<?php echo esc_attr( 'Yearly' ); ?>" <?php selected( $output_choice, 'Yearly' ); ?>><?php echo esc_html( 'Yearly' ); ?></option>
    </select>
    <?php
}

// Conversation Logging - Ver 1.7.6
function  chatbot_chatgpt_enable_conversation_logging_callback($args) {
    // Get the saved chatbot_chatgpt_enable_conversation_logging value or default to "Off"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_enable_conversation_logging', 'Off'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_enable_conversation_logging' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_enable_conversation_logging" name="chatbot_chatgpt_enable_conversation_logging">
        <option value="<?php echo esc_attr( 'On' ); ?>" <?php selected( $output_choice, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="<?php echo esc_attr( 'Off' ); ?>" <?php selected( $output_choice, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Conversation log retention period - Ver 1.7.6
function chatbot_chatgpt_conversation_log_days_to_keep_callback($args) {
    // Get the saved chatbot_chatgpt_conversation_log_days_to_keep value or default to "30"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_conversation_log_days_to_keep', '30'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_conversation_log_days_to_keep' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_conversation_log_days_to_keep" name="chatbot_chatgpt_conversation_log_days_to_keep">
        <option value="<?php echo esc_attr( '1' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '1' ); ?></option>
        <option value="<?php echo esc_attr( '7' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '7' ); ?></option>
        <option value="<?php echo esc_attr( '30' ); ?>" <?php selected( $output_choice, '30' ); ?>><?php echo esc_html( '30' ); ?></option>
        <option value="<?php echo esc_attr( '60' ); ?>" <?php selected( $output_choice, '60' ); ?>><?php echo esc_html( '60' ); ?></option>
        <option value="<?php echo esc_attr( '90' ); ?>" <?php selected( $output_choice, '90' ); ?>><?php echo esc_html( '90' ); ?></option>
        <option value="<?php echo esc_attr( '180' ); ?>" <?php selected( $output_choice, '180' ); ?>><?php echo esc_html( '180' ); ?></option>
        <option value="<?php echo esc_attr( '365' ); ?>" <?php selected( $output_choice, '365' ); ?>><?php echo esc_html( '365' ); ?></option>
    </select>
    <?php
}

// Chatbot Simple Chart - Ver 1.6.3
function generate_gd_bar_chart($labels, $data, $colors, $name) {
    // Create an image
    $width = 500;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);

    // Allocate colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $light_blue = imagecolorallocate($image, 173, 216, 230); // Light Blue color

    // Fill the background
    imagefill($image, 0, 0, $white);

    // Add title
    $title = "Visitor Interactions";
    $font = 5;
    $title_x = ($width - imagefontwidth($font) * strlen($title)) / 2;
    $title_y = 5;
    imagestring($image, $font, $title_x, $title_y, $title, $black);

    // Calculate number of bars and bar width
    $bar_count = count($data);
    // $bar_width = (int)($width / ($bar_count * 2));
    $bar_width = round($width / ($bar_count * 2));

    // Offset for the chart
    $offset_x = 25;
    $offset_y = 25;
    $top_padding = 5;

    // Bottom line
    imageline($image, 0, $height - $offset_y, $width, $height - $offset_y, $black);

    // Font size for data and labels
    $font_size = 8;

    // Draw bars
    $chart_title_height = 30; // adjust this to the height of your chart title
    for ($i = 0; $i < $bar_count; $i++) {
        $bar_height = (int)(($data[$i] * ($height - $offset_y - $top_padding - $chart_title_height)) / max($data));
        $x1 = $i * $bar_width * 2 + $offset_x;
        $y1 = $height - $bar_height - $offset_y + $top_padding;
        $x2 = ($i * $bar_width * 2) + $bar_width + $offset_x;
        $y2 = $height - $offset_y;

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = $x1 + ($bar_width / 2);
        $data_value_x = $center_x - (imagefontwidth($font_size) * strlen($data[$i]) / 2);
        $data_value_y = $y1 - 15;
        $data_value_y = max($data_value_y, 0);

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = round($x1 + ($bar_width / 2));

        $data_value_x = $center_x - (imagefontwidth(round($font_size)) * strlen($data[$i]) / 2);
        $label_x = $center_x - (imagefontwidth(round($font_size)) * strlen($labels[$i]) / 2);

        $data_value_y = $y1 - 5; // Moves the counts up or down
        $data_value_y = max($data_value_y, 0);

        // Fix: Explicitly cast to int
        $data_value_x = (int)($data_value_x);
        $data_value_y = (int)($data_value_y);

        // https://fonts.google.com/specimen/Roboto - Ver 1.6.7
        $fontFile = plugin_dir_path(__FILE__) . 'assets/fonts/roboto/Roboto-Black.ttf';

        imagettftext($image, $font_size, 0, $data_value_x, $data_value_y, $black, $fontFile, $data[$i]);

        $label_x = $center_x - ($font_size * strlen($labels[$i]) / 2) + 7; // Moves the dates left or right
        $label_y = $height - $offset_y + 15; // Moves the dates up or down

        imagettftext($image, $font_size, 0, $label_x, $label_y, $black, $fontFile, $labels[$i]);

    }

    // Save the image
    $img_path = plugin_dir_path(__FILE__) . 'assets/images/' . $name . '.png';
    imagepng($image, $img_path);

    // Free memory
    imagedestroy($image);

    return $img_path;
}


// Chatbot Charts - Ver 1.6.3
function chatbot_chatgpt_simple_chart_shortcode_function( $atts ) {

    // Check is GD Library is installed - Ver 1.6.3
    if (!extension_loaded('gd')) {
        // GD Library is installed and loaded
        // DIAG - Log the output choice
        // back_trace( 'NOTICE', 'GD Library is installed and loaded.');
        chatbot_chatgpt_general_admin_notice('Chatbot requires the GD Library to function correctly, but it is not installed or enabled on your server. Please install or enable the GD Library.');
        // DIAG - Log the output choice
        // back_trace( 'NOTICE', 'GD Library is not installed! No chart will be displayed.');
        // Disable the shortcode functionality
        return;
    }

    // Retrieve the reporting period
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));

    // Parsing shortcode attributes
    $a = shortcode_atts( array(
        'name' => 'visitorsChart_' . rand(100, 999),
        'type' => 'bar',
        'labels' => 'label',
        ), $atts );

    if(isset($atts['from_database']) && $atts['from_database'] == 'true') {

        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
        
        // Get the reporting period from the options
        $reporting_period = gesc_attr(et_option('chatbot_chatgpt_reporting_period'));
        
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($wpdb->last_error)) {
            // DIAG - Handle the error
            // back_trace( 'ERROR', 'SQL query error ' . $wpdb->last_error);
            return;
        } else if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;
        }
    }

    if (empty( $a['labels']) || empty($atts['data'])) {
        // return '<p>You need to specify both the labels and data for the chart to work.</p>';
        return '<p>No data to chart at this time. Plesae visit again later.</p>';
    }

    // Generate the chart
    $img_path = generate_gd_bar_chart($a['labels'], $atts['data'], $atts['color'] ?? null, $a['name']);
    $img_url = plugin_dir_url(__FILE__) . 'assets/images/' . $a['name'] . '.png';

    wp_schedule_single_event(time() + 60, 'chatbot_chatgpt_delete_chart', array($img_path)); // 60 seconds delay

    return '<img src="' . $img_url . '" alt="Bar Chart">';
}
// TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8
// Add shortcode
// add_shortcode('chatbot_chatgpt_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');
// add_shortcode('chatbot_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');


// Clean up ../image subdirectory - Ver 1.6.3
function chatbot_chatgpt_delete_chart() {
    $img_dir_path = plugin_dir_path(__FILE__) . 'assets/images/'; // Replace with your actual directory path
    $png_files = glob($img_dir_path . '*.png'); // Search for .png files in the directory

    foreach ($png_files as $png_file) {
        unlink($png_file); // Delete each .png file
    }
}
add_action('chatbot_chatgpt_delete_chart', 'chatbot_chatgpt_delete_chart');

// Return Interactions data in a table - Ver 1.7.8
function chatbot_chatgpt_interactions_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';

    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($wpdb->last_error)) {
            // DIAG - Handle the error
            // back_trace( 'ERROR', 'SQL query error ' . $wpdb->last_error);
            return;
        } else if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;

            $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
            $output .= '<thead><tr><th style="width: 96px;">Date</th><th style="width: 96px;">Count</th></tr></thead>';
            $output .= '<tbody>';
            foreach ($results as $result) {
                $output .= '<tr>';
                $output .= '<td style="width: 96px;">' . $result->date . '</td>';
                $output .= '<td style="width: 96px;">' . $result->count . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody>';
            $output .= '</table>';            

        return $output;

    } else {
        return '<p>No data to report at this time. Plesae visit again later.</p>';
    }

}

// Count the number of conversations stored - Ver 1.7.6
function chatbot_chatgpt_count_conversations() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $results = $wpdb->get_results("SELECT COUNT(id) AS count FROM $table_name");
    // TODO - Handle errors
    return $results[0]->count;

}

// Calculated size of the conversations stored - Ver 1.7.6
function chatbot_chatgpt_size_conversations() {

    global $wpdb;

    // Use the DB_NAME constant instead of directly accessing the protected property
    $database_name = DB_NAME;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Prepare the SQL query
    $query = $wpdb->prepare("
        SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Size_in_MB`
        FROM information_schema.TABLES
        WHERE table_schema = %s
          AND table_name = %s
    ", $database_name, $table_name);

    // Execute the query
    $results = $wpdb->get_results($query);

    // Handle errors
    if (is_wp_error($results)) {
        return 'Error: ' . $results->get_error_message();
    }

    // Check if results are returned
    if (empty($results)) {
        return 'No results found';
    }

    // Return the size in MB
    return $results[0]->Size_in_MB;

}

// Total Prompt Tokens, Completion Tokens, and Total Tokens - Ver 1.8.5
function chatbot_chatgpt_total_tokens() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
    // Calculate the start date and group by clause based on the reporting period
    if ($reporting_period === 'Daily') {
        $start_date = date('Y-m-d', strtotime("-7 days"));
        $group_by = "DATE_FORMAT(interaction_time, '%m-%d')";
    } elseif ($reporting_period === 'Monthly') {
        $start_date = date('Y-m-01', strtotime("-3 months"));
        $group_by = "DATE_FORMAT(interaction_time, '%Y-%m')";
    } else {
        $start_date = date('Y-01-01', strtotime("-3 years"));
        $group_by = "DATE_FORMAT(interaction_time, '%Y')";
    }
    
    $results = $wpdb->get_results("
        SELECT $group_by AS interaction_time, 
            SUM(CASE WHEN user_type = 'Total Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) AS count 
        FROM $table_name 
        WHERE interaction_time >= '$start_date' 
        GROUP BY $group_by
        ");
    
    if (!empty($wpdb->last_error)) {
        // Handle the error
        return '<p>Error retrieving data: ' . esc_html($wpdb->last_error) . '</p>';
    } else if (!empty($results)) {
        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result->interaction_time; // Changed from result->date to result->interaction_time
            $data[] = $result->count;
        }
        
        $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
        $output .= '<thead><tr><th>Date</th><th>Total Tokens</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($results as $result) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($result->interaction_time) . '</td>'; // Corrected to use interaction_time
            $output .= '<td>' . number_format($result->count) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
    
        return $output;
    } else {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }
    

}

function chatbot_chatgpt_download_interactions_data() {

    // Export data from the chatbot_chatgpt_interactions table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_interactions', 'Chatbot-ChatGPT-Interactions');

}

function chatbot_chatgpt_download_conversation_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Conversation Logs');
    
}

function chatbot_chatgpt_download_token_usage_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Token Usage');

}

// Download the conversation data - Ver 1.7.6
function chatbot_chatgpt_export_data( $t_table_name, $t_file_name ) {

    global $chatbot_chatgpt_plugin_dir_path;

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    global $wpdb;
    $table_name = $wpdb->prefix . $t_table_name;

    if ( $t_file_name === 'Chatbot-ChatGPT-Token Usage' ) {
        $results = $wpdb->get_results("SELECT id, session_id, user_id, interaction_time, user_type, message_text FROM $table_name WHERE user_type IN ('Prompt Tokens', 'Completion Tokens', 'Total Tokens')", ARRAY_A);
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    // Check for empty results
    if (empty($results)) {
        $message = __( 'No data in the file. Please enable conversation and interaction logging if currently off.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Check for errors
    if (!empty($wpdb->last_error)) {
        $message = __( 'Error reading table: ' . $wpdb->last_error, 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Ask user where to save the file
    $filename = $t_file_name . '-' . date('Y-m-d') . '.csv';
    // Replace spaces with - in the filename
    $filename = str_replace(' ', '-', $filename);
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($results_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace( 'ERROR', 'Failed to create directory.');
        return;
    }

    $results_csv_file = $results_dir_path . $filename;
    
    // Open file for writing
    $file = fopen($results_csv_file, 'w');

    // Check if file opened successfully
    if ($file === false) {
        $message = __( 'Error opening file for writing. Please try again.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Write headers to file
    if (isset($results[0]) && is_array($results[0])) {
        $keys = array_keys($results[0]);
        fputcsv($file, $keys);
    } else {
        $class = 'notice notice-error';
        $message = __( 'Chatbot No data in the file. Please enable conversation logging if currently off.', 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Write results to file
    foreach ($results as $result) {
        $result = array_map(function($value) {
            return $value !== null ? mb_convert_encoding($value, 'UTF-8', 'auto') : '';
        }, $result);
        fputcsv($file, $result);
    }

    // Close the file
    fclose($file);

    // Exit early if the file doesn't exist
    if (!file_exists($results_csv_file)) {
        $class = 'notice notice-error';
        $message = __( 'File not found!' . $results_csv_file, 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', 'File path: ' . $results_csv_file);

    if (!file_exists($results_csv_file)) {
        // back_trace( 'ERROR', 'File does not exist: ' . $results_csv_file);
        return;
    }
    
    if (!is_readable($results_csv_file)) {
        // back_trace( 'ERROR', 'File is not readable ' . $results_csv_file);
        return;
    }
    
    $csv_data = file_get_contents(realpath($results_csv_file));
    if ($csv_data === false) {
        $class = 'notice notice-error';
        $message = __( 'Error reading file', 'chatbot-chatgpt' );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }
    
    if (!is_writable($results_csv_file)) {
        // back_trace( 'ERROR', 'File is not writable: ' . $results_csv_file);
        return;
    }  
    
    // Deliver the file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);
    echo $csv_data;

    // Delete the file
    unlink($results_csv_file);
    exit;

}
add_action('admin_post_chatbot_chatgpt_download_conversation_data', 'chatbot_chatgpt_download_conversation_data');
add_action('admin_post_chatbot_chatgpt_download_interactions_data', 'chatbot_chatgpt_download_interactions_data');
add_action('admin_post_chatbot_chatgpt_download_token_usage_data', 'chatbot_chatgpt_download_token_usage_data');

// Function to display the reporting message - Ver 1.7.9
function chatbot_chatgpt_admin_notice() {
    $message = get_transient('chatbot_chatgpt_admin_error');
    if (!empty($message)) {
        printf('<div class="%1$s"><p><b>Chatbot: </b>%2$s</p></div>', 'notice notice-error is-dismissible', $message);
        delete_transient('chatbot_chatgpt_admin_error'); // Clear the transient after displaying the message
    }
}
add_action('admin_notices', 'chatbot_chatgpt_admin_notice');
