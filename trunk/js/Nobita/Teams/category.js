!function(e){XenForo.Team_CategoryCollapse=function(e){this.__construct(e)},XenForo.Team_CategoryCollapse.prototype={__construct:function(t){this.$element=t,this.OPEN_CLASS="open",this.$element.on("click",e.context(this,"collapse"))},collapse:function(t){t.preventDefault();var o=e(t.target);return o.hasClass(this.OPEN_CLASS)?this.close(o):this.open(o)},close:function(e){var t=e.parent();e.removeClass(this.OPEN_CLASS),t.removeClass(this.OPEN_CLASS),this.save(t.data("category"),!0)},open:function(e){var t=e.parent();t.addClass(this.OPEN_CLASS),e.addClass(this.OPEN_CLASS),this.save(t.data("category"),!1)},save:function(t,o){o=Boolean(o);var s="group_collapseCatIds",a=e.getCookie(s)||"",n=new Date;if(n.setTime(n.getTime()+6048e5),a=a.split(","),o){var r=e.grep(a,function(e){return e!=t});a=r}else a.push(t);e.setCookie(s,a.join(","),n)}},XenForo.register(".Team_CategoryCollapse","XenForo.Team_CategoryCollapse")}(jQuery,this,document);