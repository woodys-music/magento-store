(function ($) {

    $(document).ready(function(){
    
     
  $('#fisheye_disector_expand').live('click', function() {
   if ($('#fisheye_disector_expand').html() == "+ Debug Now") {
   $('.fisheye_disector').height('300px');
    $('#fisheye_disector_expand').html('- Close Debug');
   } else {
 $('.fisheye_disector').height('');
    $('#fisheye_disector_expand').html('+ Debug Now');
   } 
  });
            
    });
    
})(jQuery);