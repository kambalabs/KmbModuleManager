$(window).load(function () {
    $('#update-module-form').submit(function(){
        $('.modal :submit').prop('disabled',true);
    });
    $(document).on('click','a#confirm-remove',function(){
        $('a#confirm-remove').addClass('disabled');
    });
});
