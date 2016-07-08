;(function($){

    var $counter = 0;
    var $imagesLength = ddair_images.length;


    /**
     * Regenerate Image
     */
    var Ajax = function(){

        if( ddair_images.length > 0 ){

            $.ajax({
                url     : ajaxurl,
                type    :'POST',
                data    : {
                    action : 'dda_image_regenerate',
                    id : ddair_images[0]
                },
                success: function( image_id ){
                    $counter = $counter + 1;
                    $('body').trigger( 'dda_image_updated', { id:image_id, counter:$counter } );
                }
            });

        }

    };

    $(document).on( 'dda_image_updated', 'body', function( event, data ){
        var index = ddair_images.indexOf( data.id );

        if( index != -1) {
            ddair_images.splice( index, 1);
            $('#ddair_page h3 span').text( data.counter );
        }

        Ajax();
    });


    /**
     * Button Click
     */
    $('#ddair_start').click(function( event ){
        event.preventDefault();
        $(this).prop('disabled', true);
        $(this).parent().find('div').fadeIn(100);
        Ajax();
    });


})(jQuery);