/**
 * @author namth
 * base on code of kier
 */

/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 *
	 * @param jQuery class BRCWithdraw
	 */
	XenForo.BRCWithdraw = function($form) { this.__construct($form); };
	XenForo.BRCWithdraw.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;
			$input = $form.find('.brcAmount');
			$select = $form.find('.brcCurrency');
			this.$url = 'index.php?credits/get-withdraw-amount';
			this.$input = $input
				.change($.context(this, 'update'));

			this.$select = $select
				.change($.context(this, 'update'));

			this.update(this);
		},

		update: function(e)
		{
			var $amount = this.$input.val(),
			 $currencyId = this.$select.val();
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
			this.$form.find('.amountMessage').html(ajaxData.amountMessage);
		}
	};


	XenForo.register('.BRCWithdraw', 'XenForo.BRCWithdraw');

}
(jQuery, this, document);