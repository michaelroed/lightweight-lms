jQuery(document).ready(function($) {
    // Mark lesson as complete on page load
    if (lmsData.lesson_id && lmsData.nonce) {
        $.post(lmsData.ajax_url, {
            action: 'lms_mark_complete',
            lesson_id: lmsData.lesson_id,
            nonce: lmsData.nonce
        })
        .done(function(response) {
            if (response.success) {
                console.log('Lesson ' + lmsData.lesson_id + ' marked as complete.');
            } else {
                console.error('Failed to mark lesson complete:', response.data);
            }
        })
        .fail(function() {
            console.error('AJAX request failed.');
        });
    }
});

