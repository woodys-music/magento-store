/* 
 * v0.01 password focus, blur plugin for jQuery
 */
(function(a){a.fn.passFocusBlur=function(){return this.each(function(){var b=a(this);if(b.val()==""){b.addClass("empty_password")}b.focus(function(){b.removeClass("empty_password")});b.blur(function(){if(b.val()==""){b.addClass("empty_password")}})})}})(jQuery);