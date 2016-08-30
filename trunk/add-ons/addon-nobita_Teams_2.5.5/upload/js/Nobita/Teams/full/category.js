!function($, window, document)
{
	XenForo.Team_CategoryCollapse = function($element){ this.__construct($element); };
	XenForo.Team_CategoryCollapse.prototype =
	{
		__construct: function($element)
		{
			this.$element = $element;
			this.OPEN_CLASS = 'open';

			this.$element.on('click', $.context(this, 'collapse'));
		},

		collapse: function(e)
		{
			e.preventDefault();
			var $target = $(e.target);

			if($target.hasClass(this.OPEN_CLASS))
			{
				return this.close($target);
			}
			else
			{
				return this.open($target);
			}
		},

		close: function($target)
		{
			var $parent = $target.parent();

			$target.removeClass(this.OPEN_CLASS);
			$parent.removeClass(this.OPEN_CLASS);

			this.save($parent.data('category'), true);
		},

		open: function($target)
		{
			var $parent = $target.parent();

			$parent.addClass(this.OPEN_CLASS);
			$target.addClass(this.OPEN_CLASS);

			this.save($parent.data('category'), false);
		},

		save: function(id, remove)
		{
			remove = Boolean(remove);

			var cookieName = 'group_collapseCatIds',
				cookieValue = $.getCookie(cookieName) || '',
				date = new Date();

			date.setTime(date.getTime() + 7 * 86400 * 1000);
			cookieValue = cookieValue.split(',');

			if(remove)
			{
				var data = $.grep(cookieValue, function(value) 
				{
					return value != id;
				});

				cookieValue = data;
			}
			else
			{
				cookieValue.push(id);
			}

			$.setCookie(cookieName, cookieValue.join(','), date);
		}
	};

	XenForo.register('.Team_CategoryCollapse', 'XenForo.Team_CategoryCollapse');
}
(jQuery, this, document);