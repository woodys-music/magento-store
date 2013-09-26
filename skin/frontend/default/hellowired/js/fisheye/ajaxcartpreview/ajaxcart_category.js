var fisheye = fisheye || {};

(function ($) {
    
    

    $(document).ready(function () {

        fisheye.acp = {

            clearAllMessages: function () {
                $('.ajaxcartpreview_hide').hide();
                $('#fe_ajaxcartpreview_successcart_msg').html('');
                $('#fe_ajaxcartpreview_failedcart_msg').html('');
            },
            
            
            init: function () {


          ajaxAddToCart = function (url) {

               fisheye.acp.clearAllMessages();

                $('#fe_ajaxcartpreview_addingtocart').show();
                
                    $.ajax({
                        url: url,
                        success: function (response) {
                        
                            fisheye.acp.clearAllMessages();

                            var output = $.parseJSON(response);

                            switch (output.status) {

                            case 'S':
                                $('#fe_ajaxcartpreview_successcart_msg').html(output.status_msg);

                                if (FE_CARTPREVIEW_ENABLED) {
                                    fisheye.cpr.showCartPreview();
                                    fisheye.cpr.hideCartPreviewTimer(FE_AJAXCART_TIMETOHIDECP);

                                } else {

                                    if (FE_CARTPREVIEW_SHOWMSG) {
                                        $('#fe_ajaxcartpreview_successcart').show();
                                    }
                                }

                                break;


                            case 'F':
                                var messages = $.parseJSON(output.status_msg);
                                var error_msg = "";

                                $.each(messages, function (id, msg) {
                                    error_msg = error_msg + msg;
                                });

                                $('#fe_ajaxcartpreview_failedcart_msg').html(error_msg);
                                $('#fe_ajaxcartpreview_failedcart').show();

                                break;


                            }
                        }
                    });
                }
                
     
              var  checkProductAjax = function(url) {

                url = url.toString();

if (typeof(ProductMAP[url]) == "undefined") {
  window.location.href=url;
  return false;
}

                   if (ProductMAP[url]) {
                       url = ProductMAPUrl[url];
                       showAjaxOptions(url);
                    } else {
                      url = ProductMAPUrl[url];
                      url = url.replace("/cart/add/", '/cart/ajaxCartPreview/');
                      ajaxAddToCart(url);
                    }
                }
                
                setLocation = function(url) {
                    checkProductAjax(url);
                    
                }
    
                showAjaxOptions = function(url) {
                   
                   $.colorbox({ iframe: true, width: '800px', height: '435px', href: url});
                }


    
            }
        };


        if (FE_AJAXCART_ENABLED) {

            fisheye.acp.init();
            
            
             $('#fe_ajaxcartpreview_failedcart_close').live('click', function() {
    $('#fe_ajaxcartpreview_failedcart').hide();
 });
  
 $('#fe_addtocart_success_closebutton').live('click', function() {
    $('#fe_ajaxcartpreview_successcart').hide();
 });
 

        }
        


    });

})(jQuery);