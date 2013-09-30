var fisheye = fisheye || {}; (function($) {

	$(document).ready(function() {

		fisheye.acp = {

			init : function() {
				action = $('#product_addtocart_form').attr('action');
				action = action.replace("/add/", "/ajaxcartpreview/");
				$('#product_addtocart_form').attr('action', action);
			},
			clearAllMessages : function() {
				$('.ajaxcartpreview_hide').hide();
				$('#fe_ajaxcartpreview_successcart_msg').html('');
				$('#fe_ajaxcartpreview_failedcart_msg').html('');
			},
			triggerAjaxAddToCart : function() {

				fisheye.acp.clearAllMessages();

				$('#fe_ajaxcartpreview_addingtocart').show();
				add_to_cart_data = $('#product_addtocart_form').serialize();

				$.ajax({
					url : $('#product_addtocart_form').attr('action'),
					type : 'POST',
					data : add_to_cart_data,
					success : function(response) {

						fisheye.acp.clearAllMessages();

						var output = $.parseJSON(response);

						switch (output.status) {

							case 'S':
								$('#fe_ajaxcartpreview_successcart_msg').html(output.status_msg);

								if(FE_CARTPREVIEW_ENABLED) {
									fisheye.cpr.showCartPreview();
									fisheye.cpr.hideCartPreviewTimer(FE_AJAXCART_TIMETOHIDECP);

								} else {

									if(FE_CARTPREVIEW_SHOWMSG) {
										$('#fe_ajaxcartpreview_successcart').show();
									}
								}

								break;

							case 'F':
								var messages = $.parseJSON(output.status_msg);
								var error_msg = "";

								$.each(messages, function(id, msg) {
									error_msg = error_msg + msg;
								});

								$('#fe_ajaxcartpreview_failedcart_msg').html(error_msg);
								$('#fe_ajaxcartpreview_failedcart').show();

								break;

						}
						//alert(response);
					}
				});

			}
		};

		if(FE_AJAXCART_ENABLED) {

			$('#product_addtocart_form').keydown(function(e) {

				if(e.keyCode == 13) {

					e.preventDefault();

					productAddToCartForm.submit(1, null);
				}

			});

			productAddToCartForm.submit = function(button, url) {

				if(this.validator.validate()) {

					var form = this.form;
					var oldUrl = form.action;

					if(url) {
						form.action = url;
					}

					var e = null;

					try {

						fisheye.acp.triggerAjaxAddToCart();

					} catch (e) {

					}

					this.form.action = oldUrl;

					if(e) {
						throw e;
					}

					if(button && button != 'undefined') {
						// button.disabled = true;
					}
				}

			}

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
