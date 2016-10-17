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
	XenForo.BRCExchange = function($form) { this.__construct($form); };
	XenForo.BRCExchange.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;
			var $input = $form.find('.brcAmount');
			var $from = $form.find('.brcFrom');
			var $to = $form.find('.brcTo');
			this.$url = 'index.php?credits/get-exchange-amount';

			this.$input = $input.change($.context(this, 'update'));
			this.$to = $to.change($.context(this, 'update'));
			this.$from = $from.change($.context(this, 'update'));

			this.update(this);
		},

		update: function(e)
		{
			var $amount = parseFloat(this.$input.val()),
			$fromId = parseInt(this.$from.val()),
			$toId = parseInt(this.$to.val());
			if(!$amount || !$fromId || !$toId || ($fromId == $toId)){
				this.$form.find('.amountMessage').html('');
				this.$form.find('.loseAmountMessage').html('');
				return false;
			}
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
						to: $toId,
						from: $fromId
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
			this.$form.find('.loseAmountMessage').html(ajaxData.loseAmountMessage);
		}
	};


	XenForo.register('.BRCExchange', 'XenForo.BRCExchange');

}
(jQuery, this, document);