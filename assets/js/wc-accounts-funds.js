(function ($) {
    'use strict';

    $(document).on( 'click', '#wcaf_copy', function(){
        var copyText = document.getElementById("wcaf_ref_url");
        /* Select the text field */
        copyText.select();
        copyText.setSelectionRange(0, 99999); /*For mobile devices*/
        /* Copy the text inside the text field */
        document.execCommand("copy");
    } )
    

})(jQuery);