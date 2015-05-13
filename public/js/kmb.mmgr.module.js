$(window).load(function () {
    $('#update-module-form').submit(function(){
        $('.modal :submit').prop('disabled', true);
    });

    $(document).on('click','a#confirm-remove', function(){
        $('a#confirm-remove').addClass('disabled');
    });

    $('.btn-toggle').click(function(e) {
        e.preventDefault();
        $.getJSON($(this).find('.btn.active').attr('href'), function (data) {
            $.gritter.add({
                text: data.message,
                class_name: 'gritter-success'
            });
        });
        return false;
    });
});
