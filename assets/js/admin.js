(function($){
    function loadTransitTimes(){
        $.ajax({
            url: inventory_manager_admin.api_url + '/transit-times',
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager_admin.nonce); },
            success: function(res){
                var tbody = $('#transit-list');
                tbody.empty();
                if(!res.transit_times){ return; }
                $.each(res.transit_times, function(i,t){
                    var row = $('<tr>').attr('data-id', t.id);
                    row.append('<td><input type="text" class="transit-id" value="'+t.id+'" disabled></td>');
                    row.append('<td><input type="text" class="transit-name" value="'+t.name+'"></td>');
                    row.append('<td><button class="button save-transit">Save</button> <button class="button delete-transit">Delete</button></td>');
                    tbody.append(row);
                });
            }
        });
    }

    $(document).ready(function(){
        if(!$('#transit-settings-admin').length){ return; }
        loadTransitTimes();

        $('#add-transit-form-admin').on('submit', function(e){
            e.preventDefault();
            $.ajax({
                url: inventory_manager_admin.api_url + '/transit-times',
                method: 'POST',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager_admin.nonce); },
                data: {
                    id: $('#new_transit_id_admin').val(),
                    name: $('#new_transit_name_admin').val()
                },
                success: function(){
                    $('#new_transit_id_admin').val('');
                    $('#new_transit_name_admin').val('');
                    loadTransitTimes();
                }
            });
        });

        $(document).on('click', '#transit-list .save-transit', function(){
            var row = $(this).closest('tr');
            $.ajax({
                url: inventory_manager_admin.api_url + '/transit-times/' + row.data('id'),
                method: 'PUT',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager_admin.nonce); },
                data: { name: row.find('.transit-name').val() },
                success: function(){ loadTransitTimes(); }
            });
        });

        $(document).on('click', '#transit-list .delete-transit', function(){
            if(!confirm('Delete transit time?')) return;
            var id = $(this).closest('tr').data('id');
            $.ajax({
                url: inventory_manager_admin.api_url + '/transit-times/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager_admin.nonce); },
                success: function(){ loadTransitTimes(); }
            });
        });
    });
})(jQuery);
