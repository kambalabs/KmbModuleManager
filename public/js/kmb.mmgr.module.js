$(window).load(function () {
    $.ajax({
        url: $('#update').data('version-href'),
        dataType: "json",
        statusCode: {
            404: function() {
                $('#remove').addClass('disabled');
                $('#update').addClass('disabled');
            }
        },
        success: function (data) {
            var select = $("select#version");
            $.each(data, function (index, version) {
                select.append($('<option></option>').html(version));
            });
            select.trigger('chosen:updated');
        }
    });
});
