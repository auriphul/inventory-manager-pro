(function($){
    function loadTransitTimes(callback){
        $.ajax({
            url: inventory_manager.api_url + '/transit-times',
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
            success: function(res){
                if(!res.transit_times) return;
                var opts = '';
                $.each(res.transit_times, function(i,t){
                    opts += '<option value="'+t.id+'">'+t.name+'</option>';
                });
                $('#new_supplier_transit').html(opts);
                $('#transit-list').empty();
                $.each(res.transit_times, function(i,t){
                    var row = $('<tr data-id="'+t.id+'">');
                    row.append('<td><input type="text" class="transit-id" value="'+t.id+'" disabled></td>');
                    row.append('<td><input type="text" class="transit-name" value="'+t.name+'"></td>');
                    row.append('<td><button class="save-transit button">Edit</button> <button class="delete-transit button">Delete</button></td>');
                    $('#transit-list').append(row);
                });
                if(callback) callback();
            }
        });
    }

    function loadSuppliers(){
        $.ajax({
            url: inventory_manager.api_url + '/suppliers',
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
            success: function(res){
                $('#supplier-list').empty();
                if(!res.suppliers) return;
                $.each(res.suppliers, function(i,s){
                    var row = $('<tr data-id="'+s.id+'">');
                    row.append('<td><input type="text" class="supplier-name" value="'+s.name+'"></td>');
                    var select = $('<select class="supplier-transit"></select>');
                    $('#new_supplier_transit option').clone().appendTo(select);
                    select.val(s.transit_time);
                    row.append($('<td>').append(select));
                    row.append('<td><button class="save-supplier button">Edit</button> <button class="delete-supplier button">Delete</button></td>');
                    $('#supplier-list').append(row);
                });
            }
        });
    }

    $(document).ready(function(){
        if(!$('#settings-tab').length){ return; }
        loadTransitTimes(loadSuppliers);

        $('#add-supplier-form').on('submit', function(e){
            e.preventDefault();
            $.ajax({
                url: inventory_manager.api_url + '/suppliers',
                method: 'POST',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                data: {
                    name: $('#new_supplier_name').val(),
                    transit_time: $('#new_supplier_transit').val()
                },
                success: function(){
                    $('#new_supplier_name').val('');
                    loadSuppliers();
                }
            });
        });

        $('#add-transit-form').on('submit', function(e){
            e.preventDefault();
            $.ajax({
                url: inventory_manager.api_url + '/transit-times',
                method: 'POST',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                data: {
                    id: $('#new_transit_id').val(),
                    name: $('#new_transit_name').val()
                },
                success: function(){
                    $('#new_transit_id').val('');
                    $('#new_transit_name').val('');
                    loadTransitTimes(loadSuppliers);
                }
            });
        });

        $(document).on('click','.save-supplier', function(){
            var row = $(this).closest('tr');
            $.ajax({
                url: inventory_manager.api_url + '/suppliers/' + row.data('id'),
                method: 'PUT',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                data: {
                    name: row.find('.supplier-name').val(),
                    transit_time: row.find('.supplier-transit').val()
                },
                success: function(){ loadSuppliers(); }
            });
        });

        $(document).on('click','.delete-supplier', function(){
            if(!confirm('Delete supplier?')) return;
            var id = $(this).closest('tr').data('id');
            $.ajax({
                url: inventory_manager.api_url + '/suppliers/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                success: function(){ loadSuppliers(); }
            });
        });

        $(document).on('click','.save-transit', function(){
            var row = $(this).closest('tr');
            $.ajax({
                url: inventory_manager.api_url + '/transit-times/' + row.data('id'),
                method: 'PUT',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                data: { name: row.find('.transit-name').val() },
                success: function(){ loadTransitTimes(loadSuppliers); }
            });
        });

        $(document).on('click','.delete-transit', function(){
            if(!confirm('Delete transit time?')) return;
            var id = $(this).closest('tr').data('id');
            $.ajax({
                url: inventory_manager.api_url + '/transit-times/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce); },
                success: function(){ loadTransitTimes(loadSuppliers); }
            });
        });
    });
})(jQuery);
