$(window).load(function () {
    $.ajax({
        url: $('#update').data('version-href'),
        dataType: "json",
        success: function (data) {
            var select = $("select#version");
            $.each(data, function (index, version) {
                select.append($('<option></option>').html(version));
            });
            select.trigger('chosen:updated');
            $('#remove').removeClass('disabled');
            $('#update').removeClass('disabled');
        }
    });
});
