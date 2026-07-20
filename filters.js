jQuery(document).ready(function($) {
    function updateFilters() {
        var sector = $('#sector-filter').val();
        var stage = $('#stage-filter').val();
        var order = $('#order-filter').val();
        var url = window.location.pathname;
        var params = [];
        
        if (sector) params.push('sector=' + encodeURIComponent(sector));
        if (stage) params.push('stage=' + encodeURIComponent(stage));
        if (order && order !== 'date') params.push('orderby=' + encodeURIComponent(order));
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        window.location.href = url;
    }
    
    $('#sector-filter, #stage-filter, #order-filter').on('change', updateFilters);
});
