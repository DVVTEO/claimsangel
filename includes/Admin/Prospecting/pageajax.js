jQuery(document).ready(function($) {

    // Tab switching functionality.
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $('.bc-tab-content').hide();
        $(this).addClass('nav-tab-active');
        $('#' + targetTab).show();
    });

    // Ajax submission for General Notes.
    $('#note-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: ajaxurl, // WordPress admin AJAX URL.
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=bc_add_note_ajax',
            success: function(response) {
                if (response.success) {
                    // Update the textarea with the new note content.
                    $('#note_content').val(response.data.new_general_notes);
                    // Optionally update a notes list or display a success message.
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error adding note: ' + error);
            }
        });
    });

    // Ajax submission for adding a new key person.
    $('.bc-key-people-form form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=bc_add_key_person_ajax',
            success: function(response) {
                if (response.success) {
                    // Append the new key person row HTML to the key people list.
                    $('.bc-key-people-list').append(response.new_key_person_html);
                    // Reset the add new key person form.
                    $('.bc-key-people-form form')[0].reset();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error adding key person: ' + error);
            }
        });
    });

    // Delegate events for updating or deleting an existing key person.
    $('.bc-key-people-list').on('submit', 'form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = $form.serialize();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=bc_update_key_person_ajax',
            success: function(response) {
                if (response.success) {
                    // Replace the current form with the updated key person row HTML.
                    $form.replaceWith(response.updated_key_person_html);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating key person: ' + error);
            }
        });
    });

    // Ajax submission for Call Logging.
    $('#call-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=bc_add_call_ajax',
            success: function(response) {
                if (response.success) {
                    // Update the call log list with returned HTML.
                    $('#calls-list').html(response.calls_html);
                    // Optionally clear the form.
                    $('#call-form')[0].reset();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error logging call: ' + error);
            }
        });
    });

    // Ajax submission for Reminder Logging.
    $('#reminder-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: formData + '&action=bc_add_reminder_ajax',
            success: function(response) {
                if (response.success) {
                    // Update the reminders list with returned HTML.
                    $('#reminders-list').html(response.reminders_html);
                    // Optionally clear the form.
                    $('#reminder-form')[0].reset();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error adding reminder: ' + error);
            }
        });
    });
});