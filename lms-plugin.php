<?php
/*
Plugin Name: Lightweight LMS
Description: A lightweight LMS plugin with basic course and lesson management, video and text content, and user progress tracking.
Version: 1.2.5  
Author: Michael Roed
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Activation hook to flush rewrite rules
register_activation_hook(__FILE__, 'lms_activate_plugin');
function lms_activate_plugin() {
    // First, we need to register the post types
    lms_register_cpts();
    
    // Then flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook to flush rewrite rules
register_deactivation_hook(__FILE__, 'lms_deactivate_plugin');
function lms_deactivate_plugin() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register custom post types: Course (parent) and Lesson (child)
add_action('init', 'lms_register_cpts');
function lms_register_cpts() {
    // Register Course CPT
    register_post_type('course', array(
        'labels' => array(
            'name' => __('Courses'),
            'singular_name' => __('Course'),
            'add_new' => __('Add New Course'),
            'add_new_item' => __('Add New Course'),
            'edit_item' => __('Edit Course'),
            'view_item' => __('View Course'),
            'search_items' => __('Search Courses'),
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_menu' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'hierarchical' => false,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'show_in_rest' => true, // Enable Gutenberg editor
        'rewrite' => array('slug' => 'courses'), // Add explicit rewrite rule
    ));

    // Register Lesson CPT
    register_post_type('lesson', array(
        'labels' => array(
            'name' => __('Lessons'),
            'singular_name' => __('Lesson'),
            'add_new' => __('Add New Lesson'),
            'add_new_item' => __('Add New Lesson'),
            'edit_item' => __('Edit Lesson'),
            'view_item' => __('View Lesson'),
            'search_items' => __('Search Lessons'),
        ),
        'public' => true,
        'has_archive' => false,
        'show_in_menu' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'hierarchical' => false,
        'menu_icon' => 'dashicons-media-video',
        'show_in_rest' => true, // Enable Gutenberg editor
        'rewrite' => array('slug' => 'lessons'), // Add explicit rewrite rule
    ));
}

// Add meta boxes for video URL and lesson-course relationship
add_action('add_meta_boxes', 'lms_add_video_meta_box');
function lms_add_video_meta_box() {
    add_meta_box(
        'lms_video_meta_box',
        __('Lesson Video'),
        'lms_video_meta_box_callback',
        'lesson',
        'normal',
        'high'
    );
}

function lms_video_meta_box_callback($post) {
    wp_nonce_field('lms_save_video_meta', 'lms_video_meta_nonce');
    
    // Get current video URL if set
    $video_url = get_post_meta($post->ID, '_lms_video_url', true);
    
    ?>
    <p>
        <label for="lms_video_url"><?php _e('Video URL (YouTube, Vimeo, or direct video link):'); ?></label>
        <input type="url" 
               id="lms_video_url" 
               name="lms_video_url" 
               value="<?php echo esc_attr($video_url); ?>" 
               style="width: 100%;"
               placeholder="https://">
    </p>
    <p class="description">
        <?php _e('Enter the URL of your video. Supports YouTube, Vimeo, or direct video file links.'); ?>
    </p>
    <?php
}

// Save video meta box data
add_action('save_post_lesson', 'lms_save_video_meta', 10, 2);
function lms_save_video_meta($post_id, $post) {
    // Check if our nonce is set
    if (!isset($_POST['lms_video_meta_nonce'])) {
        return;
    }
    
    // Verify the nonce
    if (!wp_verify_nonce($_POST['lms_video_meta_nonce'], 'lms_save_video_meta')) {
        return;
    }
    
    // If this is an autosave, don't do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save the video URL
    if (isset($_POST['lms_video_url'])) {
        update_post_meta(
            $post_id,
            '_lms_video_url',
            esc_url_raw($_POST['lms_video_url'])
        );
    }
}

// Add meta box for lesson-course relationship
add_action('add_meta_boxes', 'lms_add_course_meta_box');
function lms_add_course_meta_box() {
    add_meta_box(
        'lms_course_meta_box',
        __('Course Information'),
        'lms_course_meta_box_callback',
        'lesson',
        'side',
        'high'
    );
}

// Meta box callback function
function lms_course_meta_box_callback($post) {
    wp_nonce_field('lms_save_course_meta', 'lms_course_meta_nonce');
    
    // Get current course if set
    $course_id = get_post_meta($post->ID, '_lms_parent_course', true);
    
    // Get all courses
    $courses = get_posts(array(
        'post_type' => 'course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    echo '<select name="lms_parent_course" id="lms_parent_course" required>';
    echo '<option value="">' . __('Select a Course') . '</option>';
    
    foreach ($courses as $course) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($course->ID),
            selected($course_id, $course->ID, false),
            esc_html($course->post_title)
        );
    }
    echo '</select>';
}

// Save meta box data
add_action('save_post_lesson', 'lms_save_course_meta', 10, 2);
function lms_save_course_meta($post_id, $post) {
    // Check if our nonce is set
    if (!isset($_POST['lms_course_meta_nonce'])) {
        return;
    }
    
    // Verify the nonce
    if (!wp_verify_nonce($_POST['lms_course_meta_nonce'], 'lms_save_course_meta')) {
        return;
    }
    
    // If this is an autosave, don't do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save the course relationship
    if (isset($_POST['lms_parent_course'])) {
        $course_id = sanitize_text_field($_POST['lms_parent_course']);
        
        // Verify this is a valid course
        if (get_post_type($course_id) === 'course') {
            update_post_meta($post_id, '_lms_parent_course', $course_id);
            
            // Add an admin notice that the relationship was saved
            add_action('admin_notices', function() use ($post_id, $course_id) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Lesson successfully linked to course: ' . get_the_title($course_id) . '</p>';
                echo '</div>';
            });
        } else {
            // Add an admin notice that there was an error
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>Error: Selected course is not valid. Please select a valid course.</p>';
                echo '</div>';
            });
        }
    }
}

// Filter course content to display lessons and progress
add_filter('the_content', 'lms_course_content_filter');
function lms_course_content_filter($content) {
    // Only modify content for single course pages and in the main loop
    if (!is_singular('course') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $course_id = get_the_ID();
    
    // Get all lessons for this course
    $lessons = get_posts(array(
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'meta_key'       => '_lms_parent_course',
        'meta_value'     => $course_id,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ));
    
    $lessons_html = '';

    if (!empty($lessons)) {
        // Get user progress
        $completed_lessons = array();
        if (is_user_logged_in()) {
            $completed_lessons = get_user_meta(get_current_user_id(), '_lms_completed_lessons', true);
            if (!is_array($completed_lessons)) {
                $completed_lessons = array();
            }
        }
        
        $total_lessons = count($lessons);
        $completed_count = 0;
        foreach ($lessons as $lesson) {
            if (in_array($lesson->ID, $completed_lessons)) {
                $completed_count++;
            }
        }
        $course_progress = ($total_lessons > 0) ? ($completed_count / $total_lessons) * 100 : 0;

        // Build the course overview HTML
        $lessons_html .= '<div class="lms-course-container">';
    
        // Overall Course Progress
        if (is_user_logged_in()) {
            $lessons_html .= '<div class="lms-course-progress">';
            $lessons_html .= '<h3>' . __('Course Progress', 'lightweight-lms') . '</h3>';
            $lessons_html .= '<div class="lms-progress-bar">';
            $lessons_html .= '<div class="lms-progress" style="width: ' . esc_attr($course_progress) . '%"></div>';
            $lessons_html .= '</div>';
            $lessons_html .= '<p class="lms-progress-text">';
            $lessons_html .= sprintf(__('%d of %d lessons completed', 'lightweight-lms'), $completed_count, $total_lessons);
            $lessons_html .= ' (' . round($course_progress) . '%)';
            $lessons_html .= '</p>';
            $lessons_html .= '</div>';
        }

        // Lesson List
        $lessons_html .= '<div class="lms-lesson-list">';
        $lessons_html .= '<h3>' . __('Course Lessons', 'lightweight-lms') . '</h3>';
        $lessons_html .= '<ul>';
        
        foreach ($lessons as $lesson) {
            $is_completed = in_array($lesson->ID, $completed_lessons);
            $lesson_url = get_permalink($lesson->ID);
            
            $lessons_html .= '<li class="lms-lesson-item ' . ($is_completed ? 'completed' : '') . '">';
            $lessons_html .= '<a href="' . esc_url($lesson_url) . '">' . esc_html($lesson->post_title) . '</a>';
            if ($is_completed) {
                $lessons_html .= '<span class="lms-completion-icon">✓</span>';
            }
            $lessons_html .= '</li>';
        }
        
        $lessons_html .= '</ul>';

        // Add "Start" or "Continue" button
        $course_button_html = '';
        if ($course_progress > 0 && $course_progress < 100) {
            // Find next uncompleted lesson
            $next_lesson_url = '';
            foreach ($lessons as $lesson) {
                if (!in_array($lesson->ID, $completed_lessons)) {
                    $next_lesson_url = get_permalink($lesson->ID);
                    break;
                }
            }
            if ($next_lesson_url) {
                $course_button_html = '<a href="' . esc_url($next_lesson_url) . '" class="lms-button">' . __('Continue Course', 'lightweight-lms') . '</a>';
            }
        } elseif ($course_progress == 0) {
            // Start course button links to the first lesson
            $first_lesson_url = get_permalink($lessons[0]->ID);
            $course_button_html = '<a href="' . esc_url($first_lesson_url) . '" class="lms-button">' . __('Start Course', 'lightweight-lms') . '</a>';
        }

        if (!empty($course_button_html)) {
            $lessons_html .= '<div class="lms-course-action-button">' . $course_button_html . '</div>';
        }

        $lessons_html .= '</div>'; // .lms-lesson-list
        $lessons_html .= '</div>'; // .lms-course-container
    }

    return $content . $lessons_html;
}

// Filter lesson content to display video and navigation
add_filter('the_content', 'lms_lesson_content_filter');
function lms_lesson_content_filter($content) {
    if (!is_singular('lesson') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $current_lesson_id = get_the_ID();
    $course_id = get_post_meta($current_lesson_id, '_lms_parent_course', true);

    if (!$course_id) {
        // This lesson isn't part of a course, so just return default content.
        return $content;
    }

    // Get all lessons for the parent course to build navigation
    $lessons = get_posts(array(
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'meta_key'       => '_lms_parent_course',
        'meta_value'     => $course_id,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ));
    
    // Find previous and next lessons
    $prev_lesson = null;
    $next_lesson = null;
    $current_position = 0;
    foreach($lessons as $index => $lesson) {
        if ($lesson->ID === $current_lesson_id) {
            $current_position = $index + 1;
            if ($index > 0) {
                $prev_lesson = $lessons[$index - 1];
            }
            if ($index < count($lessons) - 1) {
                $next_lesson = $lessons[$index + 1];
            }
            break;
        }
    }
    
    $total_lessons = count($lessons);
    $progress = ($total_lessons > 0) ? ($current_position / $total_lessons) * 100 : 0;

    $video_url = get_post_meta($current_lesson_id, '_lms_video_url', true);
    $video_html = '';
    if ($video_url) {
        $video_html .= '<div class="lms-video-container">';
        if (wp_oembed_get($video_url)) {
            $video_html .= wp_oembed_get($video_url);
        } else {
            $video_html .= do_shortcode('[video src="' . esc_url($video_url) . '"]');
        }
        $video_html .= '</div>';
    }

    $nav_html = '<div class="lms-navigation">';
    if ($prev_lesson) {
        $prev_url = get_permalink($prev_lesson->ID);
        $nav_html .= '<a href="' . esc_url($prev_url) . '" class="lms-button lms-prev">' . __('← Previous', 'lightweight-lms') . '</a>';
    }
    if ($next_lesson) {
        $next_url = get_permalink($next_lesson->ID);
        $nav_html .= '<a href="' . esc_url($next_url) . '" class="lms-button lms-next" data-lesson-id="' . esc_attr($current_lesson_id) . '">' . __('Next →', 'lightweight-lms') . '</a>';
    } else {
        $nav_html .= '<a href="' . get_permalink($course_id) . '" class="lms-button lms-next">' . __('Back to Course', 'lightweight-lms') . '</a>';
    }
    $nav_html .= '</div>';
    
    $progress_bar_html = '
        <div class="lms-progress-bar">
            <div class="lms-progress" style="width: ' . esc_attr($progress) . '%"></div>
        </div>
        <p class="lms-progress-text">
            ' . sprintf(__('Lesson %d of %d', 'lightweight-lms'), $current_position, $total_lessons) . '
        </p>
    ';
    
    return $progress_bar_html . $video_html . $content . $nav_html;
}

// Track user progress
function lms_mark_lesson_complete() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lms_mark_complete')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User must be logged in');
    }

    // Get lesson ID
    $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
    if (!$lesson_id) {
        wp_send_json_error('Invalid lesson ID');
    }

    // Get user ID
    $user_id = get_current_user_id();

    // Get completed lessons array
    $completed_lessons = get_user_meta($user_id, '_lms_completed_lessons', true);
    if (!is_array($completed_lessons)) {
        $completed_lessons = array();
    }

    // Add lesson to completed array if not already there
    if (!in_array($lesson_id, $completed_lessons)) {
        $completed_lessons[] = $lesson_id;
        update_user_meta($user_id, '_lms_completed_lessons', $completed_lessons);
    }

    wp_send_json_success(array(
        'message' => 'Lesson marked as complete',
        'completed_lessons' => $completed_lessons
    ));
}
add_action('wp_ajax_lms_mark_complete', 'lms_mark_lesson_complete');

// Add admin menu for progress tracking
add_action('admin_menu', 'lms_add_admin_menu');
function lms_add_admin_menu() {
    add_menu_page(
        __('LMS Progress'),
        __('LMS Progress'),
        'manage_options',
        'lms-progress',
        'lms_progress_page',
        'dashicons-chart-bar',
        30
    );
}

// Admin page callback
function lms_progress_page() {
    // Get all courses
    $courses = get_posts(array(
        'post_type' => 'course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    // Get all users
    $users = get_users(array('fields' => array('ID', 'display_name')));

    ?>
    <div class="wrap">
        <h1><?php _e('LMS Progress Tracking'); ?></h1>
        
        <?php foreach ($courses as $course) : ?>
            <div class="lms-progress-section">
                <h2><?php echo esc_html($course->post_title); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User'); ?></th>
                            <th><?php _e('Progress'); ?></th>
                            <th><?php _e('Completed Lessons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get lessons for this course
                        $lessons = get_posts(array(
                            'post_type' => 'lesson',
                            'posts_per_page' => -1,
                            'meta_key' => '_lms_parent_course',
                            'meta_value' => $course->ID
                        ));
                        $total_lessons = count($lessons);

                        foreach ($users as $user) {
                            $completed_lessons = get_user_meta($user->ID, '_lms_completed_lessons', true);
                            if (!is_array($completed_lessons)) {
                                $completed_lessons = array();
                            }

                            // Count completed lessons for this course
                            $completed_count = 0;
                            foreach ($lessons as $lesson) {
                                if (in_array($lesson->ID, $completed_lessons)) {
                                    $completed_count++;
                                }
                            }

                            // Calculate progress percentage
                            $progress = $total_lessons > 0 ? ($completed_count / $total_lessons) * 100 : 0;
                            
                            // Skip users with 0 progress
                            if ($progress == 0) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td>
                                    <div class="lms-admin-progress-bar">
                                        <div class="lms-admin-progress" style="width: <?php echo esc_attr($progress); ?>%">
                                            <?php echo round($progress); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $completed_count . ' / ' . $total_lessons; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>


    <?php
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'lms_enqueue_scripts');
add_action('admin_enqueue_scripts', 'lms_enqueue_admin_scripts');

function lms_enqueue_admin_scripts($hook) {
    // Only enqueue on our plugin's admin page
    if ($hook !== 'toplevel_page_lms-progress') {
        return;
    }
    
    wp_enqueue_style(
        'lms-admin-styles',
        plugin_dir_url(__FILE__) . 'assets/css/lms-style.css',
        array(),
        '1.0.0'
    );
}

function lms_enqueue_scripts() {
    // Load styles on both course and lesson pages
    if (is_singular('course') || is_singular('lesson')) {
        wp_enqueue_style(
            'lms-styles',
            plugin_dir_url(__FILE__) . 'assets/css/lms-style.css',
            array(),
            '1.2.4' // Version
        );
    }

    // Load script only on single lesson pages for progress tracking
    if (is_singular('lesson')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'lms-script',
            plugin_dir_url(__FILE__) . 'assets/js/lms-script.js',
            array('jquery'),
            '1.2.4', // Version
            true // Load in footer
        );
        
        // Pass data to the script
        wp_localize_script('lms-script', 'lmsData', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('lms_mark_complete'),
            'lesson_id' => get_the_ID(),
        ));
    }
}