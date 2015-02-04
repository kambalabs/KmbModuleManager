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
            for (module in modulesVersions) {
                select.append($('<option></option>').html(module));
            }
            select.trigger('chosen:updated');
        }
    });

    $(document).on('change', 'select#module', function() {
        var select = $("select#version");
        select.html('');
        $.each(modulesVersions[$("select#module option:selected").val()], function (index, version) {
            select.append($('<option></option>').html(version));
        });
        select.trigger('chosen:updated');
    });
});
