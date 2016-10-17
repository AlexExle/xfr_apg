/**
 *  [Nobita] Social Groups
 *  Do not edit
 *
 *  @package Nobita_Teams
 */
!function($, window, document, undefined)
{
    XenForo.Team_RecentStoriesScrolling = function($container) { this.__construct($container); };
    XenForo.Team_RecentStoriesScrolling.prototype =
    {
        __construct: function($container)
        {
            this.$container = $container;
            this.$flag = $(this.$container.data('flag'));
            this.$flagLoading = $('.Team_FlagLoading');

            this.nextUrl = this.$container.data('next');
            this.paddingTop = parseInt(this.$container.css('padding-top'), 10);

            this.timeout;
            this.waiting = false;
            this.offset = 50;
            this.isMobile = null;
            this.updatedUserMenu = false;

            this.parseUserAgent();

            if(!this.isMobile) {
                $(window).on('scroll', $.context(this, 'scrolling'));    
            } else {
                // Must bind click before loading more items
                this.$flag.show()
                    .find('>span').text(this.$flag.data('moretext'));

                this.$flag.on('click', $.context(this, 'loading'));
            }
        },

        parseUserAgent: function() {
            if(this.isMobile != null) {
                return this.isMobile;
            }

            if(navigator.userAgent.match(/Android/i)
                || navigator.userAgent.match(/webOS/i)
                || navigator.userAgent.match(/iPhone/i)
                || navigator.userAgent.match(/iPad/i)
                || navigator.userAgent.match(/iPod/i)
                || navigator.userAgent.match(/BlackBerry/i)
                || navigator.userAgent.match(/Windows Phone/i)
            ) {
                this.isMobile = true;
            } else {
                this.isMobile = false;
            }

            return this.isMobile;
        },

        showLoading: function() {
            if(this.$flagLoading.is(':visible')) {
                return;
            }

            this.$flagLoading.xfShow().addClass('bounceIn');
        },

        hideLoading: function() {
            if(!this.$flagLoading.is(':visible')) {
                return;
            }

            this.$flagLoading.xfHide().removeClass('bounceIn');
        },

        loading: function(e) {
            if(!this.nextUrl) {
                this.hideLoading();
                return;
            }

            if(this.waiting || this.timeout) {
                // slow connection or server slow response?
                return;
            }

            var postParams = {};
            postParams['date'] = this.$container.data('date');

            this.showLoading();

            this.timeout = setTimeout(function() {
                this.waiting = XenForo.ajax(this.nextUrl, postParams, $.context(this, 'response'));
            }.bind(this), XenForo.speed.normal);
        },

        scrolling: function(e)
        {
            var windowTop = $(window).scrollTop(),
                windowHeight = $(window).height(),
                reachMeTop = this.$flag.coords().top,
                checking;
            
            checking = Math.ceil(windowTop - reachMeTop + windowHeight + this.paddingTop);

            if(checking >= this.offset /*|| (this.offset >= Math.abs(checking))*/) {
                return this.loading();
            }
        },

        response: function(ajaxData, status)
        {
            clearTimeout(this.timeout);
            this.timeout = false;
            this.waiting = false;

            this.hideLoading();

            if(XenForo.hasResponseError(ajaxData)) {
                this.nextUrl = false;
                return false;
            }

            if(!this.updatedUserMenu) {
                this.updatedUserMenu = true;

                var documentHeight = $(document).height(),
                    $userNav = $('.Team_userNav .Team_menuPopup');
                if(documentHeight >= 1000 && $userNav.length) {
                    var $subMenu = $userNav.find('.Team_subMenu');
                    $subMenu.css({ 'max-height': '100%' });
                }
            }

            this.nextUrl = ajaxData.nextPageUrl ? ajaxData.nextPageUrl : false;
            var $html = $(ajaxData.templateHtml),
                $flag = this.$flag;

            if(!this.nextUrl) {
                $flag.xfShow().find('>span').text($flag.data('notext'));
            }

            new XenForo.ExtLoader(ajaxData, function() {
                $html.xfInsert('insertBefore', $flag, 0, 'xfFadeIn', function() {
                    // We are must try to manual activate some element.
                    new XenForo.Team_NewsFeedBox(this);

                    componentHandler.upgradeDom();
                });
            });
        }
    };

    XenForo.Team_SliderContainer = function($container) { this.__construct($container); };
    XenForo.Team_SliderContainer.prototype =
    {
        __construct: function($container)
        {
            this.$container = $container;
            this.$inner = this.$container.find('.Team_SliderContainer_Inner');
            this.owlCarousel;

            this.total = parseInt(this.$container.data('total')) || 0;
            this.itemsPerSlide = parseInt(this.$container.data('itemsperslide')) || 0;

            this.settings = {
                speed: parseInt(this.$container.data('speed')) || 1000,
                itemsPerSlide: (this.total >= this.itemsPerSlide) ? this.itemsPerSlide : this.total,
                autoplay: Boolean(this.$container.data('autoplay')),
                isRTL: ($('html').attr('dir').toLowerCase() == 'rtl') ? true : false,
            };

            if(this.itemsPerSlide)
            {
                this.init();
            }
        },

        init: function()
        {
            this.owlCarousel = this.$inner.owlCarousel(
            {
                items: this.settings['itemsPerSlide'],
                navigation: false,
                autoHeight: false,
                stopOnHover: true,
                loop: true,
                responsive: {
                    0: {
                        items: 1
                    },
                    480: {
                        items: 1
                    },
                    780: {
                        items: this.settings['itemsPerSlide']
                    }
                },
                autoplay: this.settings['autoplay'],
                slideSpeed: this.settings['speed'],
                rtl: this.settings['isRTL'],
                responsiveBaseElement: this.$inner,
                nav: true,
                navText: ['<i class=\"material-icons\">keyboard_arrow_left</i>', '<i class=\"material-icons\">keyboard_arrow_right</i>'],

                onInitialized: function(event) {
                   this.onResized(event);
                }.bind(this),
                onResized: function(event) {
                    this.onResized(event);
                }.bind(this)
            });
        },

        onResized: function(event) {
            var icon = this.$container.find('.owl-nav .material-icons'),
                $container = $(event.target);

            setTimeout(function() {
                var cssTop = parseInt($container.height() - icon.height())/2;
                icon.css({ top: cssTop });
            }, 10);
        }
    };

    XenForo.Team_NavigationContainer = function($nav){ this.__construct($nav); };
    XenForo.Team_NavigationContainer.prototype =
    {
        __construct: function($nav) {
            this.$nav = $nav;

            this.$mainNav = this.$nav.find('.Team_mainNav');
            this.$userNav = this.$nav.find('.Team_userNav');

            var resizeTimer, $html = $('html'), htmlWidth = $html.width();
            $(window).on('resize orientationchange load', function(e) {
                if(resizeTimer) return;

                if(e.type != 'load' && htmlWidth == $html.width()) {
                    return;
                }

                resizeTimer = setTimeout(function() {
                    resizeTimer = 0;
                    this.bootstrap();
                }.bind(this), 50);
            }.bind(this));

            this.$mainNav.find('#Team_navItemButton').on('click', $.context(this, 'toggleMenu'));
        },

        bootstrap: function() {
            this.width = this.$nav.width();
            this.height = this.$nav.height();
            this.spacer = 2;

            this.mainWidth = this.width - this.$userNav.width() - this.spacer;
            this.handleMainNav();
        },

        handleMainNav: function() {
            this.$mainNav.width(this.mainWidth);

            var showSel = '.selected, .Team_navItemMenu',
                $mainNav = this.$mainNav,
                $items = this.$mainNav.find('>.Team_navItem'),
                $itemActive = $items.filter('.selected'),
                $itemMenu = this.$mainNav.find('>.Team_navItemMenu'),
                $popupMenu = $($itemMenu.data('target')),
                mainCoords = this.$mainNav.coords(),
                maxWidth = this.mainWidth,
                hiddenCount = 0;

            $items.show();
            $itemMenu.hide();
            $popupMenu.empty();

            var activeCoords = $itemActive.coords();
            if((activeCoords.width + $itemMenu.width()) > maxWidth) {
                $.each($items.filter(':not(.Team_navItemMenu)').get(), function() {
                    var $this = $(this), $cloned;
                    $cloned = $this.clone();

                    $('<li />').html($cloned).appendTo($popupMenu);
                    $this.hide();
                });

                $itemMenu.show();
                return;
            }

            var isOverflowing = function(coords, element) {
                var menuCoords = $itemMenu.coords();
                // Special conditions on RTL mode.
                if(menuCoords.top > mainCoords.top) {
                    return true;
                }

                return false;
            };

            var hideTabs = function() {
                var $hideable = $items.filter(':not('+ showSel +')'),
                    overflowMenuShown = false;

                $.each($hideable.get().reverse(), function() {
                    var $this = $(this), $cloned;

                    if(isOverflowing($this.coords(), $this)) {
                        hiddenCount++;
                        $cloned = $this.clone();

                        $('<li />').html($cloned).prependTo($popupMenu);
                        $this.hide();
                    }
                });
            }();

            if(hiddenCount) {
                $itemMenu.show();
            }
        },

        toggleMenu: function(e)
        {
            e.preventDefault();

            var $target = $(e.currentTarget),
                $popupMenu = $($target.data('target')),
                visibleClass = $popupMenu.data('visible'),
                coords = $target.coords();

            if($popupMenu.is(':visible'))
            {
                $popupMenu.removeClass(visibleClass);
            }
            else
            {
                $popupMenu.addClass(visibleClass);
                var position = coords.left-$target.width();
                $popupMenu.css({ left:  (position > 0) ? position : 0 });
            }
        }
    };

    XenForo.Team_GridContainer = function($container){ this.__construct($container); };
    XenForo.Team_GridContainer.prototype =
    {
        __construct: function($container) {
            this.$container = $container;
            this.$items = this.$container.find('>.mdl-cell');

            this.resizeTimer;

            $(window).on('load resize orientationchange', function(e) {
                if(this.resizeTimer) return;

                this.resizeTimer = setTimeout(function() {
                    this.resizeTimer = 0;
                    this.bootstrap();
                }.bind(this), 2);
            }.bind(this));

            $(document).on('XenForoActivationComplete', function() {
                componentHandler.upgradeDom();
            });
        },

        bootstrap: function() {
            if(!this.$items.length) {
                return;
            }

            $.each(this.$items, function(index) {
                var $itemCell = $(this.$items.get(index)),
                    $badgeList = $itemCell.find('.Team_BadgeList'),
                    widthTemp = 0;

                if($badgeList.length) {
                    var coords = $badgeList.coords();

                    $badgeList.find('>li').get().forEach(function(node) {
                        var $node = $(node),
                            nodeCoords = $node.coords();

                        if(nodeCoords.top > coords.top) {
                            $node.hide();
                        } else {
                            $node.show();
                        }
                    });
                }
            }.bind(this));
        }
    };

    XenForo.Team_MainViewLayout = function($layout){ this.__construct($layout); };
    XenForo.Team_MainViewLayout.prototype =
    {
        __construct: function($layout) {
            this.$layout = $layout;

            this.boot();
        },

        boot: function() {
            this.resizeTimer;

            $(window).on('ready resize orientationchange', function() {
                if(this.resizeTimer) return;

                this.resizeTimer = setTimeout(function() {
                    this.cover();

                    this.resizeTimer = 0;
                }.bind(this), 50);
            }.bind(this));
        },

        cover: function() {
            var height = this.$layout.find('.coverPhoto').height(),
                $cover = this.$layout.find('.coverContainer');

            if(!$cover.hasClass('coverReposition')) {
                $cover.height(height);
            }
        },
    };

    XenForo.Team_NewsFeedBox = function($container){ this.__construct($container); };
    XenForo.Team_NewsFeedBox.prototype =
    {
        __construct: function($container)
        {
            this.$container = $container;

            this.active = $container.data('active-class');
            this.deactive = $container.data('deactive-class');

            this.$commentList = this.$container.find('.Team_CommentList');
            this.$comment = $container.find('.Team_CommentLink');
            this.$comment.on('click', $.context(this, 'showCommentForm'));

            this.$container.bind(
            {
                mouseenter: $.context(this, 'mouseEnter'),
                mouseleave: $.context(this, 'mouseLeave'),
            });

            this.commentPending = false;
            this.$commentsLoader = this.$container.find('.Team_LoadCommentsHandler');
            this.$commentsLoader.on('click', $.context(this, 'loadComments'));
        },

        mouseEnter: function(e)
        {
            this.$container.removeClass(this.deactive)
                           .addClass(this.active);
        },

        mouseLeave: function(e)
        {
            this.$container.removeClass(this.active)
                           .addClass(this.deactive);

            this.$container.find('.mdl-menu__container.is-visible')
                           .removeClass('is-visible');
        },

        showCommentForm: function(e)
        {
            e.preventDefault();

            var $target = $(e.target),
                $form = $($target.data('target'));

            $form.removeClass('invisible');
            $form.find('textarea').focus();
        },

        loadComments: function(e)
        {
            e.preventDefault();

            if(this.commentPending)
            {
                return;
            }

            var $target = $(e.target),
                $parent = $target.parent(),
                loadParams = $target.data('params') || {},
                commentDate,
                insertMethod;

            if($parent.hasClass('Team_LoadPreviousHistory'))
            {
                var $firstComment = this.$commentList.find('.Team_CommentItem').first();
                commentDate = parseInt($firstComment.data('date'), 10);

                loadParams['is_previous'] = 1;
                insertMethod = 'prependTo';
            }
            else
            {
                var $lastComment = this.$commentList.find('.Team_CommentItem').last();
                commentDate = parseInt($lastComment.data('date'), 10);

                loadParams['is_previous'] = 0;
                insertMethod = 'appendTo';
            }

            if(!commentDate)
            {
                return;
            }
            loadParams['comment_date'] = commentDate;
            
            this.commentPending = XenForo.ajax($target.attr('href'), loadParams, function(ajaxData, status)
            {
                this.commentPending = false;

                if(XenForo.hasResponseError(ajaxData))
                {
                    return false;
                }

                if(!ajaxData.commentsUnshown) {
                    $parent.xfRemove();
                }

                var $html = $(ajaxData.templateHtml);
                new XenForo.ExtLoader(ajaxData, function()
                {
                    $html.xfInsert(insertMethod, this.$commentList, 0, 'xfFadeIn', function()
                    {
                        componentHandler.upgradeDom();
                    });
                }.bind(this));
            }.bind(this));
        }
    };

    XenForo.register('.Team_MainViewLayout', 'XenForo.Team_MainViewLayout');
    XenForo.register('.Team_NavContainer', 'XenForo.Team_NavigationContainer');
    XenForo.register('.Team_GridContainer', 'XenForo.Team_GridContainer');
    XenForo.register('.Team_SliderContainer', 'XenForo.Team_SliderContainer');
    XenForo.register('.Team_NewsFeedBox', 'XenForo.Team_NewsFeedBox');
    XenForo.register('.Team_RecentStoriesScrolling', 'XenForo.Team_RecentStoriesScrolling');
}
(jQuery, this, document);
