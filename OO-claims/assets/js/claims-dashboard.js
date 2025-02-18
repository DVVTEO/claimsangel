            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    loadStylesheet(href) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }
    
    initializeComponents() {
        this.initializeDataTable();
        this.initializeCharts();
        this.initializeFilters();
    }
    
    initializeDataTable() {
        this.claimsTable = new DataTable('#claims-table', {
            serverSide: true,
            processing: true,
            ajax: {
                url: ajaxurl,
                data: (d) => {
                    return {
                        ...d,
                        action: 'get_claims_data',
                        nonce: this.config.nonce,
                        filters: this.getActiveFilters()
                    };
                }
            },
            columns: [
                { data: 'id', title: 'ID' },
                { data: 'client_name', title: 'Client' },
                { data: 'status', title: 'Status' },
                { data: 'created_date', title: 'Created' },
                { data: 'last_updated', title: 'Last Updated' },
                { 
                    data: null,
                    title: 'Actions',
                    render: (data) => this.renderActions(data)
                }
            ],
            order: [[3, 'desc']],
            pageLength: this.config.itemsPerPage || 25,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'excel', 'pdf', 'print'
            ]
        });
    }
    
    initializeCharts() {
        this.claimStatusChart = new Chart(
            document.getElementById('claims-status-chart'),
            {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107',
                            '#17a2b8'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        );
        
        this.updateCharts();
    }
    
    initializeFilters() {
        this.dateRange = new DateRangePicker('#date-filter', {
            ranges: {
                'Today': [moment(), moment()],
                'This Week': [moment().startOf('week'), moment().endOf('week')],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months'), moment()]
            },
            opens: 'left'
        });
    }
    
    bindEvents() {
        // Refresh button
        document.querySelector('#refresh-data').addEventListener('click', () => {
            this.refreshData();
        });
        
        // Date range filter
        this.dateRange.on('apply.daterangepicker', (ev, picker) => {
            this.updateDateRange(picker.startDate, picker.endDate);
        });
        
        // Status filter
        document.querySelectorAll('.status-filter').forEach(filter => {
            filter.addEventListener('change', () => {
                this.refreshData();
            });
        });
        
        // Bulk actions
        document.querySelector('#bulk-action-apply').addEventListener('click', () => {
            this.handleBulkAction();
        });
    }
    
    async refreshData() {
        try {
            await Promise.all([
                this.claimsTable.ajax.reload(),
                this.updateCharts()
            ]);
            this.showNotification('Data refreshed successfully');
        } catch (error) {
            this.showError('Failed to refresh data');
            console.error('Refresh error:', error);
        }
    }
    
    async updateCharts() {
        try {
            const response = await this.fetchChartData();
            this.updateChartData(response);
        } catch (error) {
            console.error('Chart update error:', error);
        }
    }
    
    async fetchChartData() {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'get_claims_statistics',
                nonce: this.config.nonce,
                ...this.getActiveFilters()
            })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    }
    
    updateChartData(data) {
        this.claimStatusChart.data.labels = data.labels;
        this.claimStatusChart.data.datasets[0].data = data.values;
        this.claimStatusChart.update();
    }
    
    getActiveFilters() {
        return {
            dateRange: {
                start: this.dateRange.startDate.format('YYYY-MM-DD'),
                end: this.dateRange.endDate.format('YYYY-MM-DD')
            },
            statuses: Array.from(document.querySelectorAll('.status-filter:checked'))
                .map(checkbox => checkbox.value)
        };
    }
    
    renderActions(data) {
        return `
            <div class="btn-group">
                <button class="btn btn-sm btn-primary" onclick="claimsDashboard.viewClaim(${data.id})">
                    View
                </button>
                <button class="btn btn-sm btn-warning" onclick="claimsDashboard.editClaim(${data.id})">
                    Edit
                </button>
                ${this.canDeleteClaims() ? `
                    <button class="btn btn-sm btn-danger" onclick="claimsDashboard.deleteClaim(${data.id})">
                        Delete
                    </button>
                ` : ''}
            </div>
        `;
    }
    
    canDeleteClaims() {
        return this.config.userCan?.delete_claims || false;
    }
    
    showNotification(message, type = 'success') {
        const toast = new Toast({
            message,
            type,
            duration: 3000
        });
        toast.show();
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
}