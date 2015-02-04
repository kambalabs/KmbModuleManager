$(window).load(function () {

    $('#update-module.modal').on('show.bs.modal', function (e) {
        $.ajax({
            url: $(e.relatedTarget).data('version-href'),
            dataType: "json",
            success: function (data) {
                var select = $("select#version");
                select.html('');
                $.each(data, function (index, version) {
                    select.append($('<option></option>').html(version));
                });
                select.trigger('chosen:updated');
            }
        });
    });
});
