$(window).load(function () {
    var parser = document.createElement('a');
    parser.href = document.URL;
    var prefixUriMatch = parser.pathname.match(/(\/env\/[0-9]+)/);
    var prefixUri = prefixUriMatch ? prefixUriMatch[1] : '';

    var modulesVersions = [];
    $.ajax({
        url: prefixUri + '/module-manager/modules/installable',
        dataType: "json",
        success: function (data) {
            modulesVersions = data;
            var select = $("select#module");
            for (var module in modulesVersions) {
                select.append($('<option></option>').html(module));
            }
            select.trigger('chosen:updated');
            $('#btn-add-module').removeClass('disabled');
        },
        complete: function () {
            var icon = $('#btn-add-module > span:first-child');
            icon.removeClass('glyphicon-hourglass');
            icon.addClass('glyphicon-plus-sign');
        }
    });

    $(document).on('change', 'select#module', function() {
        var select = $("select#version");
        select.html('');
        $.each(modulesVersions[$("select#module option:selected").val()], function (version, formattedVersion) {
            select.append($('<option value="' + version + '"></option>').html(formattedVersion));
        });
        select.trigger('chosen:updated');
    });

    $('#add-module-form').submit(function() {
        $(".modal :submit").prop('disabled', true);
    });

    $(document).on('change', 'select#version,select#module', function() {
        if ($('select#version option:selected').text().match(/^\d+(\.\d+)*$/)) {
            $('#auto-update-zone').hide();
        } else {
            $('#auto-update-zone').show();
        }
    });
    $(document).on('change', 'select#module', function() {
        $('#auto-update').attr('checked', false);
    });
});
