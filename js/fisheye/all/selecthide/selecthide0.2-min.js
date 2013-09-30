/* 
 * v0.02 Select hide plugin for jQuery
 */
(function($){$.fn.selectHide=function(){return this.each(function(){var $this=$(this);$this.css('opacity','0');$this.wrap('<div class="select_wrap" />');$this.before('<span class="selected_text"></span><span class="btn"></span>');var selected_text=$this.parent().children('.selected_text');new_text=$this.children('option:selected').text();if(new_text==''){new_text=$this.children('optgroup').children('option:selected').text();}
selected_text.html(new_text);$this.change(function(){new_text=$this.children('option:selected').text();if(new_text==''){new_text=$this.children('optgroup').children('option:selected').text();}
selected_text.html(new_text);});});};})(jQuery);