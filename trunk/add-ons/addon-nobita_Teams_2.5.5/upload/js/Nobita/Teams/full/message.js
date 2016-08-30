!function($, window, document, _undefined)
{
	XenForo.Team_SharePostInline = function($form){ this.__construct($form); };
	XenForo.Team_SharePostInline.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;
			this.$textarea = $form.find('textarea');
			this.$buttons = $form.find('.button');
			this.submitPending;

			this.$stories = $(this.$form.data('stories'));

			this.$form.on('submit', $.context(this, 'submit'));
		},

		submit: function(e)
		{
			e.preventDefault();

			this.$buttons.prop('disabled', true).addClass('disabled');

			if(this.submitPending) {
				this.submitPending.abort();
				this.submitPending = false;
			}

			var postData = this.$form.serializeArray(),
				$wysiwyg = this.$textarea.data('XenForo.BbCodeWysiwygEditor');

			if($wysiwyg) {
				$wysiwyg.resetEditor();
			}

			this.submitPending = XenForo.ajax(this.$form.attr('action'), postData, $.context(this, 'response'));

			this.$textarea.val('');
			this.$form.find(".AttachmentList.New li:not(#AttachedFileTemplate)").xfRemove();
            this.$form.find(".AttachmentInsertAllBlock").xfRemove();
		},

		response: function(ajaxData, status)
		{
			this.submitPending = false;

			setTimeout(function() {
				this.$buttons.prop('disabled', false).removeClass('disabled');
			}.bind(this), 2000);

			if(XenForo.hasResponseError(ajaxData)) {
				return false;
			}

			var $html = $(ajaxData.templateHtml);
			new XenForo.ExtLoader(ajaxData, function() {
				$html.xfInsert('prependTo', this.$stories, 0, 'xfSlideDown', function() {
					new XenForo.Team_NewsFeedBox(this);
					componentHandler.upgradeDom();
				});
			}.bind(this));
		}
	};

	XenForo.Team_WatchUnwatchContent = function($link) { this.__construct($link); };
    XenForo.Team_WatchUnwatchContent.prototype =
    {
        __construct: function($link)
        {
            this.$link = $link;

            this.$link.on('click', $.context(this, 'watchUnwatch'));
        },

        watchUnwatch: function(e)
        {
        	e.preventDefault();

        	var $target = $(e.currentTarget),
        		watch = 'watch',
        		unwatch = 'unwatch',
        		postData = {};

        	if($target.hasClass(unwatch)) {
        		postData['stop'] = 1;
        	}

        	XenForo.ajax($target.attr('href'), postData, function(ajaxData, status) {
        		if(XenForo.hasResponseError(ajaxData)) {
        			return false;
        		}

        		if($target.hasClass(watch))
        		{
        			$target.removeClass(watch).addClass(unwatch);
        		}
        		else
        		{
        			$target.removeClass(unwatch).addClass(watch);
        		}

        		$target.find('span').text(ajaxData.linkPhrase);
        	});
        }
    };

    XenForo.Team_CommentComposingHandler = function($textarea){ this.__construct($textarea); };
    XenForo.Team_CommentComposingHandler.prototype =
    {
    	__construct: function($textarea)
    	{
    		this.$textarea = $textarea;
    		this.$commentList = $(this.$textarea.data('container'));

    		this.waiting = false;
    		this.KEY_CODES = {
    			ENTER: 13,
    			SHIFT: 16
    		};

    		this.$textarea.on('keydown', $.context(this, 'composing'));
    		this.$textarea.on('submit', $.context(this, 'submit'));
    	},

    	composing: function(e)
    	{
    		var $target = $(e.target);
    		if((e.keyCode == 13 || e.charCode == 13) && !e.shiftKey) {
    			$target.trigger('submit');
    			return;
    		}
    	},

    	submit: function(e)
    	{
    		var $target = $(e.target),
    			postUrl = $target.data('url').trim(),
    			params = $target.data('params') || {};

    		if(this.waiting || !postUrl.length || !this.$commentList.length) {
    			return;
    		}

    		$target.prop('disabled', true).addClass('disabled');

    		params = XenForo.ajaxDataPush(params, 'message', $target.val().trim());
    		this.waiting = true;

    		XenForo.ajax(postUrl, params, $.context(this, 'response'));
    	},

    	response: function(ajaxData, status)
    	{
    		this.waiting = false;
    		this.$textarea.prop('disabled', false)
    			.removeClass('disabled')
    			.css({ height: 'auto' })
    			.val('')
    			.blur();

    		if(XenForo.hasResponseError(ajaxData)) {
    			return false;
    		}

    		var $html = $(ajaxData.templateHtml);
    		new XenForo.ExtLoader(ajaxData, function() {
    			$html.xfInsert('appendTo', this.$commentList, 0, 'xfSlideUp', function() {
    				componentHandler.upgradeDom();
    			});
    		}.bind(this));
    	}
    };

	XenForo.register('.Team_WatchUnwatchContent', 'XenForo.Team_WatchUnwatchContent');
	XenForo.register('.Team_SharePostInline', 'XenForo.Team_SharePostInline');
	XenForo.register('.Team_CommentComposingHandler', 'XenForo.Team_CommentComposingHandler');
}
(jQuery, this, document);


