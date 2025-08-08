# Lightweight LMS

A simple, lightweight Learning Management System (LMS) for WordPress. This plugin allows you to create courses and lessons, manage user progress, and display content with a clean, simple interface.

## Features

- **Custom Post Types:** Separate CPTs for "Courses" and "Lessons".
- **Course & Lesson Management:** Easily create, edit, and link lessons to courses.
- **User Progress Tracking:** Automatically tracks which lessons a user has completed.
- **Admin Progress View:** A dashboard page in the WordPress admin to see the progress of all users across all courses.
- **Frontend Display:** Automatically displays lessons on the course page, along with a progress bar and completion status.
- **Video Support:** Embed videos from YouTube, Vimeo, or direct links into your lessons.
- **Simple Navigation:** "Next" and "Previous" buttons for easy navigation between lessons.

## Installation

1.  **Download the Plugin:**
    *   Clone the repository: `git clone https://github.com/michaelroed/lightweight-lms.git`
    *   Or download the ZIP file from the repository page.

2.  **Upload to WordPress:**
    *   In your WordPress dashboard, go to **Plugins > Add New > Upload Plugin**.
    *   Choose the ZIP file you downloaded and click **"Install Now"**.

3.  **Activate:**
    *   Once installed, click **"Activate Plugin"**.

The plugin will automatically create the necessary database structure and flush the rewrite rules.

## Usage

1.  **Create a Course:**
    *   Navigate to the **Courses** menu in your WordPress admin dashboard.
    *   Click **"Add New"** to create a new course. Give it a title and add a description in the main content editor.

2.  **Create Lessons:**
    *   Navigate to the **Lessons** menu.
    *   Click **"Add New"** to create a lesson.
    *   Add a title and content. You can also add a video URL in the "Lesson Video" box.
    *   **Crucially**, in the "Course Information" box in the sidebar, select the course this lesson belongs to.

3.  **View Your Course:**
    *   That's it! Navigate to the course page on the frontend of your site. The course description and its associated lessons will be displayed automatically.

## How Progress is Tracked

- When a logged-in user visits a lesson page, that lesson is automatically marked as complete for them.
- The progress bar on the main course page will update in real-time to reflect their completion percentage.
- Administrators can view the progress of all users by navigating to the **LMS Progress** page in the WordPress dashboard.

## Contributing

Contributions are welcome! Please feel free to submit a pull request or open an issue for any bugs or feature requests.

