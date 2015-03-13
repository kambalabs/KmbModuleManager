$(window).load(function () {
    $.ajax({
        url: $('#update').data('version-href'),
        dataType: "json",
        success: function (data) {
            var select = $("select#version");
            $.each(data, function (index, version) {
                select.append($('<option value="' + version + '"></option>').html(version.replace(/^[0-9.]*-[0-9]*-/, '')));
            });
            select.trigger('chosen:updated');
            $('#remove').removeClass('disabled');
            $('#update').removeClass('disabled');
        }
    });
    $('#update-module-form').submit(function(){
        $('.modal :submit').prop('disabled',true);
    });
    $(document).on('click','a#confirm-remove',function(){
        $('a#confirm-remove').addClass('disabled');
    });
});
