/**
 * Import Manager JavaScript
 * 
 * Handles file uploads and AJAX interactions for prospect importing
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-22 12:04:11
 * Author: DVVTEO
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        const $importForm = $('#importForm');
        const $importResults = $('#import-results');

        // Handle form submission
        $importForm.on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'process_prospect_upload');
            formData.append('security', prospectImport.nonce);

            // Show loading state
            $(this).find('input[type="submit"]').prop('disabled', true);

            // Make AJAX request
            $.ajax({
                url: prospectImport.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        displayResults(response.data);
                        // After successful upload, show temporary records
                        displayTempRecords();
                    } else {
                        alert(response.data.message || 'Upload failed. Please try again.');
                    }
                },
                error: function() {
                    alert('Server error occurred. Please try again.');
                },
                complete: function() {
                    $importForm.find('input[type="submit"]').prop('disabled', false);
                }
            });
        });

        // Handle country approval
        $(document).on('click', '.approve-country', function() {
            const $button = $(this);
            const countryCode = $button.data('country');

            $button.prop('disabled', true);

            $.ajax({
                url: prospectImport.ajaxurl,
                type: 'POST',
                data: {
                    action: 'approve_country_prospects',
                    security: prospectImport.nonce,
                    country: countryCode
                },
                success: function(response) {
                    if (response.success) {
                        // Update the table to show approved status
                        updateCountryTable(countryCode, response.data);
                        // After approval, refresh temporary records
                        displayTempRecords();
                    } else {
                        alert(response.data.message || 'Approval failed. Please try again.');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Server error occurred. Please try again.');
                    $button.prop('disabled', false);
                }
            });
        });

        /**
         * Display results in country-specific tables
         * @param {Object} data The response data containing prospects grouped by country
         */
        function displayResults(data) {
            $importResults.show();

            // Clear existing table data
            $('.prospects-table tbody').empty();

            // Populate tables for each country
            Object.keys(data.prospects).forEach(function(country) {
                const $table = $(`#country-${country} .prospects-table tbody`);
                const prospects = data.prospects[country];

                prospects.forEach(function(prospect) {
                    const row = `
                        <tr class="${prospect.status === 'duplicate' ? 'duplicate' : ''}">
                            <td>${escapeHtml(prospect.business_name)}</td>
                            <td>${escapeHtml(prospect.web_address)}</td>
                            <td>${escapeHtml(prospect.phone_number)}</td>
                            <td>${escapeHtml(prospect.linkedin_profile)}</td>
                            <td>${getStatusLabel(prospect.status)}</td>
                        </tr>
                    `;
                    $table.append(row);
                });

                // Show/hide country section based on data
                $(`#country-${country}`).toggle(prospects.length > 0);
            });
        }

        /**
         * Display temporary records for review
         */
        function displayTempRecords() {
            $.ajax({
                url: prospectImport.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_temp_prospects',
                    security: prospectImport.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Get the table body
                        const $tbody = $('.ca-import-table tbody');
                        $tbody.empty();

                        // Add each record to the table
                        response.data.forEach(function(record) {
                            const row = `
                                <tr>
                                    <td>${escapeHtml(record.business_name)}</td>
                                    <td>${escapeHtml(record.web_address)}</td>
                                    <td>${escapeHtml(record.phone_number)}</td>
                                    <td>${escapeHtml(record.linkedin_profile)}</td>
                                    <td>${escapeHtml(record.country)}</td>
                                    <td>${getStatusLabel(record.status)}</td>
                                    <td>${formatDate(record.created_at)}</td>
                                </tr>
                            `;
                            $tbody.append(row);
                        });

                        // Show the temp records section if we have data
                        $('.ca-import-container').toggle(response.data.length > 0);
                    }
                },
                error: function() {
                    alert('Failed to load temporary records.');
                }
            });
        }

        /**
         * Update country table after approval
         * @param {string} countryCode The country code
         * @param {Object} data The response data
         */
        function updateCountryTable(countryCode, data) {
            const $table = $(`#country-${countryCode} .prospects-table tbody`);
            $table.find('tr').each(function() {
                $(this).find('td:last').text('Approved');
            });
            $(`#country-${countryCode} .approve-country`).remove();
        }

        /**
         * Get formatted status label
         * @param {string} status The status code
         * @returns {string} Formatted status label
         */
        function getStatusLabel(status) {
            const labels = {
                'valid': 'Valid',
                'duplicate': 'Duplicate Found',
                'approved': 'Approved',
                'error': 'Error',
                'pending': 'Pending'
            };
            return labels[status] || status;
        }

        /**
         * Format date for display
         * @param {string} dateString The date string to format
         * @returns {string} Formatted date string
         */
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }

        /**
         * Escape HTML special characters
         * @param {string} unsafe The unsafe string
         * @returns {string} Escaped safe string
         */
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initial load of temporary records
        displayTempRecords();
    });
})(jQuery);