class ClaimsDashboard {
    constructor() {
        this.initializeComponents();
        this.bindEvents();
    }
    
    initializeComponents() {
        // Initialize DataTables for better table management
        this.claimsTable = new DataTable('#claims-table', {
            pageLength: 25,
            responsive: true,
            ajax: {
                url: ajaxurl,
                data: {
                    action: 'get_claims_data',
                    nonce: cmVars.nonce
                }
            }
        });
        
        // Initialize date range picker
        this.dateRange = new DateRangePicker('#date-range', {
            ranges: {
                'Today': [moment(), moment()],
                'This Week': [moment().startOf('week'), moment().endOf('week')],
                'This Month': [moment().startOf('month'), moment().endOf('month')]
            }
        });
    }
    
    bindEvents() {
        document.querySelector('#refresh-data').addEventListener('click', () => {
            this.refreshData();
        });
        
        this.dateRange.on('apply.daterangepicker', (ev, picker) => {
            this.updateDateRange(picker.startDate, picker.endDate);
        });
    }
    
    async refreshData() {
        try {
            await this.claimsTable.ajax.reload();
            this.showNotification('Data refreshed successfully');
        } catch (error) {
            this.showError('Failed to refresh data');
        }
    }
}