/**
 * @author namth
 * base on code of kier
 */

/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 *
	 * @param jQuery class AutoCompleteResource
	 */
	// *********************************************************************

	XenForo.AutoCompleteResource = function($element) { this.__construct($element); };
	XenForo.AutoCompleteResource.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input;
			if (XenForo.AutoCompleteResource.defaultUrl === null)
			{
				if ($('html').hasClass('Admin'))
				{
					XenForo.AutoCompleteResource.defaultUrl = 'admin.php?credit-resource-log/search-name&_xfResponseType=json';
				}
				else
				{
					XenForo.AutoCompleteResource.defaultUrl = null;
				}
			}

			this.url = $input.data('acUrl') || XenForo.AutoCompleteResource.defaultUrl;
			this.extraFields = $input.data('acExtraFields');

			var options = {
				multiple: $input.hasClass('AcSingle') ? false : ',', // mutiple value joiner
				minLength: 2, // min word length before lookup
				queryKey: 'q',
				extraParams: {},
				jsonContainer: 'results',
				autoSubmit: XenForo.isPositive($input.data('autoSubmit'))
			};
			if ($input.data('acOptions'))
			{
				options = $.extend(options, $.parseJSON($input.data('acOptions')));
			}

			if (options.autoSubmit)
			{
				options.multiple = false;
			}

			this.multiple = options.multiple;
			this.minLength = options.minLength;
			this.queryKey = options.queryKey;
			this.extraParams = options.extraParams;
			this.jsonContainer = options.jsonContainer;
			this.autoSubmit = options.autoSubmit;

			this.selectedResult = 0;
			this.loadVal = '';
			this.$results = false;
			this.resultsVisible = false;

			$input.attr('autocomplete', 'off')
				.keydown($.context(this, 'keystroke'))
				.keypress($.context(this, 'operaKeyPress'))
				.blur($.context(this, 'blur'));

			$input.closest('form').submit($.context(this, 'hideResults'));
		},

		keystroke: function(e)
		{
			var code = e.keyCode || e.charCode, prevent = true;

			switch(code)
			{
				case 40: this.selectResult(1); break; // down
				case 38: this.selectResult(-1); break; // up
				case 27: this.hideResults(); break; // esc
				case 13: // enter
					if (this.resultsVisible)
					{
						this.insertSelectedResult();
					}
					else
					{
						prevent = false;
					}
					break;

				default:
					prevent = false;
					if (this.loadTimer)
					{
						clearTimeout(this.loadTimer);
					}
					this.loadTimer = setTimeout($.context(this, 'load'), 200);

					if (this.$results)
					{
						this.$results.hide().empty();
						this.resultsVisible = false;
					}
			}

			if (prevent)
			{
				e.preventDefault();
			}
			this.preventKey = prevent;
		},

		operaKeyPress: function(e)
		{
			if ($.browser.opera && this.preventKey)
			{
				e.preventDefault();
			}
		},

		blur: function(e)
		{
			clearTimeout(this.loadTimer);

			// timeout ensures that clicks still register
			setTimeout($.context(this, 'hideResults'), 250);

			if (this.xhr)
			{
				this.xhr.abort();
				this.xhr = false;
			}
		},

		load: function()
		{
			
			var lastLoad = this.loadVal,
				params = this.extraParams;
			if (this.loadTimer)
			{
				clearTimeout(this.loadTimer);
			}
			this.loadVal = this.getPartialValue();

			if (this.loadVal == '')
			{
				this.hideResults();
				return;
			}
			if (this.loadVal == lastLoad)
			{
				return;
			}
			if (this.loadVal.length < this.minLength)
			{
				return;
			}
			params[this.queryKey] = this.loadVal;

			if (this.extraFields != '')
			{
				$(this.extraFields).each(function()
				{
					params[this.name] = $(this).val();
				});
			}
			if (this.xhr)
			{
				this.xhr.abort();
			}
			
			this.xhr = XenForo.ajax(
				this.url,
				params,
				$.context(this, 'showResults'),
				{ global: false, error: false }
			);
		},

		hideResults: function()
		{
			this.resultsVisible = false;

			if (this.$results)
			{
				this.$results.hide();
			}
		},

		showResults: function(results)
		{
			var offset = this.$input.offset(),
				maxZIndex = 0,
				i,
				filterRegex;

			if (this.xhr)
			{
				this.xhr = false;
			}

			if (!results)
			{
				this.hideResults();
				return;
			}

			if (this.jsonContainer)
			{
				if (!results[this.jsonContainer])
				{
					this.hideResults();
					return;
				}
				else
				{
					results = results[this.jsonContainer];
				}
			}

			this.resultsVisible = false;

			if (!this.$results)
			{
				this.$results = $('<ul />')
					.css({position: 'absolute', display: 'none'})
					.addClass('autoCompleteList')
					.appendTo(document.body);

				this.$input.parents().each(function(i, el)
				{
					var $el = $(el),
						zIndex = parseInt($el.css('z-index'), 10);

					if (zIndex > maxZIndex)
					{
						maxZIndex = zIndex;
					}
				});

				this.$results.css('z-index', maxZIndex + 1000);
			}
			else
			{
				this.$results.hide().empty();
			}

			filterRegex = new RegExp('(' + XenForo.regexQuote(this.$input.val()) + ')', 'i');

			for (i in results)
			{
				$('<li />')
					.css('cursor', 'pointer')
					.data('autoComplete', i)
					.click($.context(this, 'resultClick'))
					.mouseenter($.context(this, 'resultMouseEnter'))
					.html(results[i]['resource_title'].replace(filterRegex, '<strong>$1</strong>'))
					.appendTo(this.$results)
					.prepend($('<img class="autoCompleteAvatar" />').attr('src', results[i]['avatar']));
			}

			if (!this.$results.children().length)
			{
				return;
			}

			this.selectResult(0, true);

			var css = {
				top: offset.top + this.$input.outerHeight(),
				left: offset.left
			};

			if (XenForo.isRTL())
			{
				css.right = $('html').width() - offset.left - this.$input.outerWidth();
				css.left = 'auto';
			}

			if (!this.resizeBound)
			{
				$(window).bind('resize', $.context(this, 'hideResults'));
			}

			this.$results.css(css).show();
			this.resultsVisible = true;
		},

		resultClick: function(e)
		{
			e.stopPropagation();

			this.addValue($(e.currentTarget).data('autoComplete'));
			this.hideResults();
			this.$input.focus();
		},

		resultMouseEnter: function (e)
		{
			this.selectResult($(e.currentTarget).index(), true);
		},

		selectResult: function(shift, absolute)
		{
			var sel, children;

			if (!this.$results)
			{
				return;
			}

			if (absolute)
			{
				this.selectedResult = shift;
			}
			else
			{
				this.selectedResult += shift;
			}

			sel = this.selectedResult;
			children = this.$results.children();
			children.each(function(i)
			{
				if (i == sel)
				{
					$(this).addClass('selected');
				}
				else
				{
					$(this).removeClass('selected');
				}
			});

			if (sel < 0 || sel >= children.length)
			{
				this.selectedResult = -1;
			}
		},

		insertSelectedResult: function()
		{
			var res, ret = false;

			if (!this.resultsVisible)
			{
				return false;
			}

			if (this.selectedResult >= 0)
			{
				res = this.$results.children().get(this.selectedResult);
				if (res)
				{
					this.addValue($(res).data('autoComplete'));
					ret = true;
				}
			}

			this.hideResults();

			return ret;
		},

		addValue: function(value)
		{
			if (!this.multiple)
			{
				this.$input.val(value);
			}
			else
			{
				var values = this.getFullValues();
				if (value != '')
				{
					if (values.length)
					{
						value = ' ' + value;
					}
					values.push(value + this.multiple + ' ');
				}
				this.$input.val(values.join(this.multiple));
			}

			if (this.autoSubmit)
			{
				this.$input.closest('form').submit();
			}
		},

		getFullValues: function()
		{
			var val = this.$input.val();

			if (val == '')
			{
				return [];
			}

			if (!this.multiple)
			{
				return [val];
			}
			else
			{
				splitPos = val.lastIndexOf(this.multiple);
				if (splitPos == -1)
				{
					return [];
				}
				else
				{
					val = val.substr(0, splitPos);
					return val.split(this.multiple);
				}
			}
		},

		getPartialValue: function()
		{
			var val = this.$input.val(),
				splitPos;

			if (!this.multiple)
			{
				return $.trim(val);
			}
			else
			{
				splitPos = val.lastIndexOf(this.multiple);
				if (splitPos == -1)
				{
					return $.trim(val);
				}
				else
				{
					return $.trim(val.substr(splitPos + this.multiple.length));
				}
			}
		}
	};
	XenForo.AutoCompleteResource.defaultUrl = null;


	// *********************************************************************
// Register form controls
	XenForo.register('input, textarea', function(i)
	{
		var $this = $(this);
		// AutoCompleteResource
		if ($this.hasClass('AutoCompleteResource'))
		{
			XenForo.create('XenForo.AutoCompleteResource', this);
		}
	});
	

}
(jQuery, this, document);