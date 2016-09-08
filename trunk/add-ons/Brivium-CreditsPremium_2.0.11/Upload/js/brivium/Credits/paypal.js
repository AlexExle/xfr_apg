/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 *
	 * @param jQuery class BRCPpurchaseForm
	 */
	XenForo.BRCPpurchaseForm = function($form) { this.__construct($form); };
	XenForo.BRCPpurchaseForm.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;

			this.$select = $form.find('.brcCurrency')
				.change($.context(this, 'changePriceOptions'));

			this.changePriceOptions(this);
		},

		changePriceOptions: function(e)
		{
			this.$form.find('#amountMessage').html('');
			var $currencyId = this.$select.val();
			if (this.xhrpo)
			{
				this.xhrpo.abort();
				this.xhrpo = false;
			}
			if (!this.xhrpo)
			{
				$triggerLink = 'index.php?payment-paypal/price-options';
				this.xhrpo = XenForo.ajax(
					$triggerLink,
					{
						currency_id: $currencyId
					},
					$.context(this, 'replaceContent')
					,{cache: false}
				);
			}
		},

		replaceContent: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}
			delete(this.xhrpo);
			delete(this.xhr);
			$this = this;
			$this.$form.find('.BRCPPriceOptions').html('');
			$(ajaxData.templateHtml).xfInsert('appendTo', $this.$form.find('.BRCPPriceOptions'));

			this.$url = 'index.php?payment-paypal/get-forum-amount.json';
			this.$input = this.$form.find('.brcAmount').change($.context(this, 'updateAmount'));
			$this = this;
			this.$form.find('input.packageNumber').click($.context(this, 'updateAmount'));
			this.updateAmount(this);
		},

		changeSubmit: function($enable)
		{
			if($enable >0){
				this.$form.find('#ctrl_submit')
					.removeAttr('disabled')
					.removeClass('disabled')
					.trigger('DisablerDisabled');
			}else{
				this.$form.find('#amountMessage').html('');
				this.$form.find('#ctrl_submit')
					.prop('disabled', true)
					.addClass('disabled')
					.trigger('DisablerEnabled');
			}
		},

		updateAmount: function(e)
		{
			if(this.$form.find('.packageNumber').length){
				$amount = this.$form.find('input.packageNumber:checked').val();
			}else{
				$amount = this.$input.val();
			}
			$this.changeSubmit($amount);
			var $currencyId = this.$select.val();
			if (this.xhr)
			{
				this.xhr.abort();
				this.xhr = false;
			}
			if (!this.xhr)
			{
				$triggerLink = this.$url+'&_xfResponseType=json';
				this.xhr = XenForo.ajax(
					$triggerLink,
					{
						amount: $amount,
						xfToken: $('#ctrl_xfToken').val(),
						currency_id: $currencyId
					},
					$.context(this, 'addComplete')
					,{cache: false}
				);
			}
		},


		addComplete: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}
			delete(this.xhr);

			this.$form.find('#amountMessage').html(ajaxData.amountMessage);
			this.$form.find('#transactionTitle').val(ajaxData.transactionTitle);
			this.$form.find('#ctrl_custom').val(ajaxData.custom);
		}
	};

	XenForo.register('.BRCPpurchaseForm', 'XenForo.BRCPpurchaseForm');

}
(jQuery, this, document);