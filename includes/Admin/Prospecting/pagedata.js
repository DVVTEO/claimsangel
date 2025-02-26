jQuery(document).ready(function($) {

    // ----------------------------------------
    // Helper Function: Refresh Call Reminders
    // ----------------------------------------
    // This function makes an AJAX call to fetch the latest reminders
    // and updates the UI accordingly.
    function refreshCallReminders() {
        var prospect_id = $("input[name='prospect_id']").val();
        var nonce = myAjaxSettings.getNonce;
        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "get_call_reminders",
                nonce: nonce,
                prospect_id: prospect_id
            },
            success: function(response) {
                if (response.success) {
                    $("#reminders-list").html(response.data);
                } else {
                    alert("Failed to refresh reminders: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert("Error refreshing reminders: " + error);
            }
        });
    }

    // ----------------------------------------
    // Helper Function: Refresh Key People
    // ----------------------------------------
    // This function makes an AJAX call to fetch the latest key people data
    // and updates the UI accordingly.
    function refreshKeyPeople() {
        var prospect_id = $("input[name='prospect_id']").val();
        var nonce = myAjaxSettings.getKeyPeopleNonce;
        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "get_key_people",
                nonce: nonce,
                prospect_id: prospect_id
            },
            success: function(response) {
                if (response.success) {
                    $("#key-people-list").html(response.data);
                } else {
                    alert("Failed to refresh key people: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert("Error refreshing key people: " + error);
            }
        });
    }

    // ----------------------------------------
    // UI Component Initialization
    // ----------------------------------------

    // Initialize the jQuery UI dialog (lightbox) for adding call reminders.
    // The dialog is hidden by default and uses custom modal styling.
    $("#reminder-lightbox").dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        height: 'auto',
        dialogClass: 'custom-dialog',
        buttons: {
            "Close": function() {
                $(this).dialog("close");
            }
        }
    });

    // Activate the datepicker on the reminder date input field.
    // The selected date will follow the format "dd-mm-yy".
    $("#reminder_date").datepicker({
        dateFormat: "dd-mm-yy"
    });

    // ----------------------------------------
    // Event Handlers
    // ----------------------------------------

    // Open the reminder dialog when the "Call Reminder" button is clicked.
    $("#callreminder").on("click", function(e) {
        e.preventDefault();
        $("#reminder-lightbox").dialog("open");
    });

    // Handle the "Save" button click inside the reminder dialog.
    // This gathers the form data and sends an AJAX request to save the reminder.
    $("#save-reminder").on("click", function(e) {
        e.preventDefault();
        var prospectID = $("input[name='prospect_id']").val(); // Hidden input should contain the prospect ID.
        var reminderDate = $("#reminder_date").val();
        var reminderNotes = $("#reminder_notes").val();
        var nonce = myAjaxSettings.saveNonce;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: "save_call_back_reminder",
                nonce: nonce,
                prospect_id: prospectID,
                reminder_date: reminderDate,
                reminder_notes: reminderNotes
            },
            success: function(response) {
                if (response.success) {
                    alert("Reminder saved successfully.");
                    // Update the reminders list with the new HTML.
                    $("#reminders-list").html(response.data);
                    // Close the reminder dialog.
                    $("#reminder-lightbox").dialog("close");
                    // Optionally, refresh the entire reminders section.
                    refreshCallReminders();
                } else {
                    alert("Error: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert("An error occurred: " + error);
            }
        });
    });

    // Delegate click event for delete buttons within the reminders list.
    // This ensures that clicking "Delete" on a dynamically generated element works.
    $('#reminders-list').on('click', '.delete-reminder', function(e) {
        e.preventDefault();
        var index = $(this).data('index'); // Get the reminder index.
        var prospectID = $('input[name="prospect_id"]').val();
        var nonce = myAjaxSettings.deleteNonce;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_call_reminder',
                nonce: nonce,
                prospect_id: prospectID,
                index: index
            },
            success: function(response) {
                if (response.success) {
                    // Update the reminders list with the returned HTML.
                    $('#reminders-list').html(response.data);
                } else {
                    alert("Error: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert("Ajax error: " + error);
            }
        });
    });

    // Handle the submission of the General Notes form using AJAX.
    // This prevents a full page refresh and saves the notes asynchronously.
    $('#note-form').on('submit', function(e) {
        e.preventDefault();
        var prospect_id = $('input[name="prospect_id"]').val();
        var note_content = $('#note_content').val();
        var nonce = $('#save_general_notes_nonce').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_general_notes',
                nonce: nonce,
                prospect_id: prospect_id,
                note_content: note_content
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });
    
    // Handle deletion of a key person via AJAX.
    $('#key-people-list').on('click', '.delete-key-person', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        // Assume the prospect_id is stored in a hidden input field (the first one in the page).
        var prospect_id = $('input[name="prospect_id"]').first().val();
        var nonce = myAjaxSettings.deleteKeyPersonNonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_key_person',
                nonce: nonce,
                prospect_id: prospect_id,
                index: index
            },
            success: function(response) {
                if (response.success) {
                    // Update the key people list container with the new table HTML.
                    $('#key-people-list').html(response.data);
                } else {
                    alert("Error: " + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert("An error occurred: " + error);
            }
        });
    });
    
    
    
    // Handle AJAX submission for adding a new key person.
$('#new-key-person-form').on('submit', function(e) {
    e.preventDefault(); // Prevent the default form submission.

    // Gather form data.
    var prospect_id = $('input[name="prospect_id"]', this).val();
    var firstName   = $('input[name="kp_first_name"]', this).val();
    var lastName    = $('input[name="kp_last_name"]', this).val();
    var role        = $('input[name="kp_role"]', this).val();
    var email       = $('input[name="kp_email"]', this).val();
    var phone       = $('input[name="kp_phone"]', this).val();
    var nonce       = myAjaxSettings.keyPersonNonce;

    // Send the AJAX request.
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'save_key_person',
            nonce: nonce,
            prospect_id: prospect_id,
            kp_first_name: firstName,
            kp_last_name: lastName,
            kp_role: role,
            kp_email: email,
            kp_phone: phone
        },
        success: function(response) {
            if (response.success) {
                // Clear the form fields.
                $('#new-key-person-form')[0].reset();
                // Update the key people list container with the returned table HTML.
                $('#key-people-list').html(response.data);
                // Refresh key people to ensure consistent state
                refreshKeyPeople();
            } else {
                alert("Error: " + response.data);
            }
        },
        error: function(xhr, status, error) {
            alert("An error occurred: " + error);
        }
    });
});

    // Enable tab switching functionality for CRM modules.
    // When a tab is clicked, display its corresponding content and update active styling.
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $('.bc-tab-content').hide();
        $(this).addClass('nav-tab-active');
        $('#' + targetTab).show();
        
        // Refresh data when switching to specific tabs
        if (targetTab === 'reminders') {
            refreshCallReminders();
        } else if (targetTab === 'key_people') {
            refreshKeyPeople();
        }
    });
    
    // Handler for saving the edited call log note.
$(document).on('click', '.save-call-log', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var logIndex = $row.data('index'); // Make sure the row has the correct data-index attribute
    var prospect_id = $('input[name="prospect_id"]').val();
    var newNote = $row.find('.edit-note-input').val();
    var nonce = myAjaxSettings.updateCallLogNonce;
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'update_call_log',
            nonce: nonce,
            prospect_id: prospect_id,
            log_index: logIndex,
            new_note: newNote
        },
        success: function(response) {
            if(response.success) {
                // Update the call logs container with the refreshed HTML.
                $('#call-logs-container').html(response.data);
            } else {
                alert("Error: " + response.data);
            }
        },
        error: function(xhr, status, error) {
            alert("Error: " + error);
        }
    });
});
    // Handler for editing the call log note.
    $(document).on('click', '.edit-call-log', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var currentNote = $row.find('.call-log-note').text().trim();
    
    // Store the original note so we can restore it on cancel.
    $row.data('original-note', currentNote);
    
    // Replace the note cell with a textarea for editing.
    $row.find('.call-log-note').html('<textarea class="edit-note-input" style="width:100%; height:80px;">' + currentNote + '</textarea>');
    
    // Change the button text to "Save" and add a "Cancel" button if it doesn't already exist.
    $btn.text('Save').removeClass('edit-call-log').addClass('save-call-log');
    if ($row.find('.cancel-call-log').length === 0) {
        $row.find('td:last').append(' <button class="button cancel-call-log">Cancel</button>');
    }
});

// Handler for canceling the inline edit.
$(document).on('click', '.cancel-call-log', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var $row = $btn.closest('tr');
    // Retrieve the original note stored in the row's data.
    var originalNote = $row.data('original-note');
    
    // Restore the note cell with the original note.
    $row.find('.call-log-note').text(originalNote);
    
    // Revert the "Save" button back to "Edit" and remove the cancel button.
    $row.find('.save-call-log').text('Edit').removeClass('save-call-log').addClass('edit-call-log');
    $btn.remove();
});

});