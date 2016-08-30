/*! jQuery Migrate v1.2.1 | (c) 2005, 2013 jQuery Foundation, Inc. and other contributors | jquery.org/license */
jQuery.migrateMute===void 0&&(jQuery.migrateMute=!0),function(e,t,n){function r(n){var r=t.console;i[n]||(i[n]=!0,e.migrateWarnings.push(n),r&&r.warn&&!e.migrateMute&&(r.warn("JQMIGRATE: "+n),e.migrateTrace&&r.trace&&r.trace()))}function a(t,a,i,o){if(Object.defineProperty)try{return Object.defineProperty(t,a,{configurable:!0,enumerable:!0,get:function(){return r(o),i},set:function(e){r(o),i=e}}),n}catch(s){}e._definePropertyBroken=!0,t[a]=i}var i={};e.migrateWarnings=[],!e.migrateMute&&t.console&&t.console.log&&t.console.log("JQMIGRATE: Logging is active"),e.migrateTrace===n&&(e.migrateTrace=!0),e.migrateReset=function(){i={},e.migrateWarnings.length=0},"BackCompat"===document.compatMode&&r("jQuery is not compatible with Quirks Mode");var o=e("<input/>",{size:1}).attr("size")&&e.attrFn,s=e.attr,u=e.attrHooks.value&&e.attrHooks.value.get||function(){return null},c=e.attrHooks.value&&e.attrHooks.value.set||function(){return n},l=/^(?:input|button)$/i,d=/^[238]$/,p=/^(?:autofocus|autoplay|async|checked|controls|defer|disabled|hidden|loop|multiple|open|readonly|required|scoped|selected)$/i,f=/^(?:checked|selected)$/i;a(e,"attrFn",o||{},"jQuery.attrFn is deprecated"),e.attr=function(t,a,i,u){var c=a.toLowerCase(),g=t&&t.nodeType;return u&&(4>s.length&&r("jQuery.fn.attr( props, pass ) is deprecated"),t&&!d.test(g)&&(o?a in o:e.isFunction(e.fn[a])))?e(t)[a](i):("type"===a&&i!==n&&l.test(t.nodeName)&&t.parentNode&&r("Can't change the 'type' of an input or button in IE 6/7/8"),!e.attrHooks[c]&&p.test(c)&&(e.attrHooks[c]={get:function(t,r){var a,i=e.prop(t,r);return i===!0||"boolean"!=typeof i&&(a=t.getAttributeNode(r))&&a.nodeValue!==!1?r.toLowerCase():n},set:function(t,n,r){var a;return n===!1?e.removeAttr(t,r):(a=e.propFix[r]||r,a in t&&(t[a]=!0),t.setAttribute(r,r.toLowerCase())),r}},f.test(c)&&r("jQuery.fn.attr('"+c+"') may use property instead of attribute")),s.call(e,t,a,i))},e.attrHooks.value={get:function(e,t){var n=(e.nodeName||"").toLowerCase();return"button"===n?u.apply(this,arguments):("input"!==n&&"option"!==n&&r("jQuery.fn.attr('value') no longer gets properties"),t in e?e.value:null)},set:function(e,t){var a=(e.nodeName||"").toLowerCase();return"button"===a?c.apply(this,arguments):("input"!==a&&"option"!==a&&r("jQuery.fn.attr('value', val) no longer sets properties"),e.value=t,n)}};var g,h,v=e.fn.init,m=e.parseJSON,y=/^([^<]*)(<[\w\W]+>)([^>]*)$/;e.fn.init=function(t,n,a){var i;return t&&"string"==typeof t&&!e.isPlainObject(n)&&(i=y.exec(e.trim(t)))&&i[0]&&("<"!==t.charAt(0)&&r("$(html) HTML strings must start with '<' character"),i[3]&&r("$(html) HTML text after last tag is ignored"),"#"===i[0].charAt(0)&&(r("HTML string cannot start with a '#' character"),e.error("JQMIGRATE: Invalid selector string (XSS)")),n&&n.context&&(n=n.context),e.parseHTML)?v.call(this,e.parseHTML(i[2],n,!0),n,a):v.apply(this,arguments)},e.fn.init.prototype=e.fn,e.parseJSON=function(e){return e||null===e?m.apply(this,arguments):(r("jQuery.parseJSON requires a valid JSON string"),null)},e.uaMatch=function(e){e=e.toLowerCase();var t=/(chrome)[ \/]([\w.]+)/.exec(e)||/(webkit)[ \/]([\w.]+)/.exec(e)||/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(e)||/(msie) ([\w.]+)/.exec(e)||0>e.indexOf("compatible")&&/(mozilla)(?:.*? rv:([\w.]+)|)/.exec(e)||[];return{browser:t[1]||"",version:t[2]||"0"}},e.browser||(g=e.uaMatch(navigator.userAgent),h={},g.browser&&(h[g.browser]=!0,h.version=g.version),h.chrome?h.webkit=!0:h.webkit&&(h.safari=!0),e.browser=h),a(e,"browser",e.browser,"jQuery.browser is deprecated"),e.sub=function(){function t(e,n){return new t.fn.init(e,n)}e.extend(!0,t,this),t.superclass=this,t.fn=t.prototype=this(),t.fn.constructor=t,t.sub=this.sub,t.fn.init=function(r,a){return a&&a instanceof e&&!(a instanceof t)&&(a=t(a)),e.fn.init.call(this,r,a,n)},t.fn.init.prototype=t.fn;var n=t(document);return r("jQuery.sub() is deprecated"),t},e.ajaxSetup({converters:{"text json":e.parseJSON}});var b=e.fn.data;e.fn.data=function(t){var a,i,o=this[0];return!o||"events"!==t||1!==arguments.length||(a=e.data(o,t),i=e._data(o,t),a!==n&&a!==i||i===n)?b.apply(this,arguments):(r("Use of jQuery.fn.data('events') is deprecated"),i)};var j=/\/(java|ecma)script/i,w=e.fn.andSelf||e.fn.addBack;e.fn.andSelf=function(){return r("jQuery.fn.andSelf() replaced by jQuery.fn.addBack()"),w.apply(this,arguments)},e.clean||(e.clean=function(t,a,i,o){a=a||document,a=!a.nodeType&&a[0]||a,a=a.ownerDocument||a,r("jQuery.clean() is deprecated");var s,u,c,l,d=[];if(e.merge(d,e.buildFragment(t,a).childNodes),i)for(c=function(e){return!e.type||j.test(e.type)?o?o.push(e.parentNode?e.parentNode.removeChild(e):e):i.appendChild(e):n},s=0;null!=(u=d[s]);s++)e.nodeName(u,"script")&&c(u)||(i.appendChild(u),u.getElementsByTagName!==n&&(l=e.grep(e.merge([],u.getElementsByTagName("script")),c),d.splice.apply(d,[s+1,0].concat(l)),s+=l.length));return d});var Q=e.event.add,x=e.event.remove,k=e.event.trigger,N=e.fn.toggle,T=e.fn.live,M=e.fn.die,S="ajaxStart|ajaxStop|ajaxSend|ajaxComplete|ajaxError|ajaxSuccess",C=RegExp("\\b(?:"+S+")\\b"),H=/(?:^|\s)hover(\.\S+|)\b/,A=function(t){return"string"!=typeof t||e.event.special.hover?t:(H.test(t)&&r("'hover' pseudo-event is deprecated, use 'mouseenter mouseleave'"),t&&t.replace(H,"mouseenter$1 mouseleave$1"))};e.event.props&&"attrChange"!==e.event.props[0]&&e.event.props.unshift("attrChange","attrName","relatedNode","srcElement"),e.event.dispatch&&a(e.event,"handle",e.event.dispatch,"jQuery.event.handle is undocumented and deprecated"),e.event.add=function(e,t,n,a,i){e!==document&&C.test(t)&&r("AJAX events should be attached to document: "+t),Q.call(this,e,A(t||""),n,a,i)},e.event.remove=function(e,t,n,r,a){x.call(this,e,A(t)||"",n,r,a)},e.fn.error=function(){var e=Array.prototype.slice.call(arguments,0);return r("jQuery.fn.error() is deprecated"),e.splice(0,0,"error"),arguments.length?this.bind.apply(this,e):(this.triggerHandler.apply(this,e),this)},e.fn.toggle=function(t,n){if(!e.isFunction(t)||!e.isFunction(n))return N.apply(this,arguments);r("jQuery.fn.toggle(handler, handler...) is deprecated");var a=arguments,i=t.guid||e.guid++,o=0,s=function(n){var r=(e._data(this,"lastToggle"+t.guid)||0)%o;return e._data(this,"lastToggle"+t.guid,r+1),n.preventDefault(),a[r].apply(this,arguments)||!1};for(s.guid=i;a.length>o;)a[o++].guid=i;return this.click(s)},e.fn.live=function(t,n,a){return r("jQuery.fn.live() is deprecated"),T?T.apply(this,arguments):(e(this.context).on(t,this.selector,n,a),this)},e.fn.die=function(t,n){return r("jQuery.fn.die() is deprecated"),M?M.apply(this,arguments):(e(this.context).off(t,this.selector||"**",n),this)},e.event.trigger=function(e,t,n,a){return n||C.test(e)||r("Global events are undocumented and deprecated"),k.call(this,e,t,n||document,a)},e.each(S.split("|"),function(t,n){e.event.special[n]={setup:function(){var t=this;return t!==document&&(e.event.add(document,n+"."+e.guid,function(){e.event.trigger(n,null,t,!0)}),e._data(this,n,e.guid++)),!1},teardown:function(){return this!==document&&e.event.remove(document,n+"."+e._data(this,n)),!1}}})}(jQuery,window);

/*
 jQuery Tools dev - The missing UI library for the Web

 dateinput/dateinput.js
 overlay/overlay.js
 overlay/overlay.apple.js
 rangeinput/rangeinput.js
 scrollable/scrollable.js
 scrollable/scrollable.autoscroll.js
 scrollable/scrollable.navigator.js
 tabs/tabs.js
 toolbox/toolbox.expose.js
 toolbox/toolbox.history.js
 toolbox/toolbox.mousewheel.js
 tooltip/tooltip.js
 tooltip/tooltip.slide.js

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/

 jquery.event.wheel.js - rev 1
 Copyright (c) 2008, Three Dub Media (http://threedubmedia.com)
 Liscensed under the MIT License (MIT-LICENSE.txt)
 http://www.opensource.org/licenses/mit-license.php
 Created: 2008-07-01 | Updated: 2008-07-14

 -----

*/
(function(a,v){function n(a,b){a=""+a;for(b=b||2;a.length<b;)a="0"+a;return a}function f(a,b,c,d){var g=b.getDate(),e=b.getDay(),k=b.getMonth(),f=b.getFullYear(),g={d:g,dd:n(g),ddd:m[d].shortDays[e],dddd:m[d].days[e],m:k+1,mm:n(k+1),mmm:m[d].shortMonths[k],mmmm:m[d].months[k],yy:String(f).slice(2),yyyy:f};a=h[a](c,b,g,d);return q.html(a).html()}function d(a){return parseInt(a,10)}function c(a,b){return a.getFullYear()===b.getFullYear()&&a.getMonth()==b.getMonth()&&a.getDate()==b.getDate()}function b(a){if(a!==
v){if(a.constructor==Date)return a;if("string"==typeof a){var b=a.split("-");if(3==b.length)return new Date(d(b[0]),d(b[1])-1,d(b[2]));if(!/^-?\d+$/.test(a))return;a=d(a)}b=new Date;b.setDate(b.getDate()+a);return b}}function e(e,h){function k(b,c,d){e.attr("readonly")?l.hide(d):(D=b,L=b.getFullYear(),N=b.getMonth(),M=b.getDate(),d||(d=a.Event("api")),"click"!=d.type||a.browser.msie||e.focus(),d.type="beforeChange",O.trigger(d,[b]),d.isDefaultPrevented()||(e.val(f(c.formatter,b,c.format,c.lang)),
d.type="change",O.trigger(d),e.data("date",b),l.hide(d)))}function w(b){b.type="onShow";O.trigger(b);a(document).on("keydown.d",function(b){if(b.ctrlKey)return!0;var c=b.keyCode;if(8==c||46==c)return e.val(""),l.hide(b);if(27==c||9==c)return l.hide(b);if(0<=a(r).index(c)){if(!G)return l.show(b),b.preventDefault();var d=a("#"+p.weeks+" a"),h=a("."+p.focus),g=d.index(h);h.removeClass(p.focus);74==c||40==c?g+=7:75==c||38==c?g-=7:76==c||39==c?g+=1:72!=c&&37!=c||--g;41<g?(l.addMonth(),h=a("#"+p.weeks+
" a:eq("+(g-42)+")")):0>g?(l.addMonth(-1),h=a("#"+p.weeks+" a:eq("+(g+42)+")")):h=d.eq(g);h.addClass(p.focus);return b.preventDefault()}if(34==c)return l.addMonth();if(33==c)return l.addMonth(-1);if(36==c)return l.today();13==c&&(a(b.target).is("select")||a("."+p.focus).click());return 0<=a([16,17,18,9]).index(c)});a(document).on("click.d",function(b){var c=b.target;c.id==p.root||a(c).parents("#"+p.root).length||c==e[0]||y&&c==y[0]||l.hide(b)})}var l=this,q=new Date,u=q.getFullYear(),p=h.css,n=m[h.lang],
x=a("#"+p.root),F=x.find("#"+p.title),y,H,I,L,N,M,D=e.attr("data-value")||h.value||e.val(),A=e.attr("min")||h.min,E=e.attr("max")||h.max,G,P;0===A&&(A="0");D=b(D)||q;A=b(A||new Date(u+h.yearRange[0],1,1));E=b(E||new Date(u+h.yearRange[1]+1,1,-1));if(!n)throw"Dateinput: invalid language: "+h.lang;"date"==e.attr("type")&&(P=e.clone(),u=P.wrap("<div/>").parent().html(),u=a(u.replace(/type/i,"type=text data-orig-type")),h.value&&u.val(h.value),e.replaceWith(u),e=u);e.addClass(p.input);var O=e.add(l);
if(!x.length){x=a("<div><div><a/><div/><a/></div><div><div/><div/></div></div>").hide().css({position:"absolute"}).attr("id",p.root);x.children().eq(0).attr("id",p.head).end().eq(1).attr("id",p.body).children().eq(0).attr("id",p.days).end().eq(1).attr("id",p.weeks).end().end().end().find("a").eq(0).attr("id",p.prev).end().eq(1).attr("id",p.next);F=x.find("#"+p.head).find("div").attr("id",p.title);if(h.selectors){var J=a("<select/>").attr("id",p.month),K=a("<select/>").attr("id",p.year);F.html(J.add(K))}for(var u=
x.find("#"+p.days),S=0;7>S;S++)u.append(a("<span/>").text(n.shortDays[(S+h.firstDay)%7]));a("body").append(x)}h.trigger&&(y=a("<a/>").attr("href","#").addClass(p.trigger).click(function(a){h.toggle?l.toggle():l.show();return a.preventDefault()}).insertAfter(e));var Q=x.find("#"+p.weeks),K=x.find("#"+p.year),J=x.find("#"+p.month);a.extend(l,{show:function(b){if(!e.attr("disabled")&&!G&&(b=a.Event(),b.type="onBeforeShow",O.trigger(b),!b.isDefaultPrevented())){a.each(g,function(){this.hide()});G=!0;
J.off("change").change(function(){l.setValue(d(K.val()),d(a(this).val()))});K.off("change").change(function(){l.setValue(d(a(this).val()),d(J.val()))});H=x.find("#"+p.prev).off("click").click(function(a){H.hasClass(p.disabled)||l.addMonth(-1);return!1});I=x.find("#"+p.next).off("click").click(function(a){I.hasClass(p.disabled)||l.addMonth();return!1});l.setValue(D);var c=e.offset();/iPad/i.test(navigator.userAgent)&&(c.top-=a(window).scrollTop());x.css({top:c.top+e.outerHeight(!0)+h.offset[0],left:c.left+
h.offset[1]});h.speed?x.show(h.speed,function(){w(b)}):(x.show(),w(b));return l}},setValue:function(g,e,w){var f=-1<=d(e)?new Date(d(g),d(e),d(w==v||isNaN(w)?1:w)):g||D;f<A?f=A:f>E&&(f=E);"string"==typeof g&&(f=b(g));g=f.getFullYear();e=f.getMonth();w=f.getDate();-1==e?(e=11,g--):12==e&&(e=0,g++);if(!G)return k(f,h),l;N=e;L=g;M=w;w=(new Date(g,e,1-h.firstDay)).getDay();var t=(new Date(g,e+1,0)).getDate(),r=(new Date(g,e-1+1,0)).getDate(),u;if(h.selectors){J.empty();a.each(n.months,function(b,c){A<
new Date(g,b+1,1)&&E>new Date(g,b,0)&&J.append(a("<option/>").html(c).attr("value",b))});K.empty();for(var f=q.getFullYear(),m=f+h.yearRange[0];m<f+h.yearRange[1];m++)A<new Date(m+1,0,1)&&E>new Date(m,0,0)&&K.append(a("<option/>").text(m));J.val(e);K.val(g)}else F.html(n.months[e]+" "+g);Q.empty();H.add(I).removeClass(p.disabled);for(var m=w?0:-7,x,y;m<(w?42:35);m++)x=a("<a/>"),0===m%7&&(u=a("<div/>").addClass(p.week),Q.append(u)),m<w?(x.addClass(p.off),y=r-w+m+1,f=new Date(g,e-1,y)):m>=w+t?(x.addClass(p.off),
y=m-t-w+1,f=new Date(g,e+1,y)):(y=m-w+1,f=new Date(g,e,y),c(D,f)?x.attr("id",p.current).addClass(p.focus):c(q,f)&&x.attr("id",p.today)),A&&f<A&&x.add(H).addClass(p.disabled),E&&f>E&&x.add(I).addClass(p.disabled),x.attr("href","#"+y).text(y).data("date",f),u.append(x);Q.find("a").click(function(b){var c=a(this);c.hasClass(p.disabled)||(a("#"+p.current).removeAttr("id"),c.attr("id",p.current),k(c.data("date"),h,b));return!1});p.sunday&&Q.find("."+p.week).each(function(){var b=h.firstDay?7-h.firstDay:
0;a(this).children().slice(b,b+1).addClass(p.sunday)});return l},setMin:function(a,c){A=b(a);c&&D<A&&l.setValue(A);return l},setMax:function(a,c){E=b(a);c&&D>E&&l.setValue(E);return l},today:function(){return l.setValue(q)},addDay:function(a){return this.setValue(L,N,M+(a||1))},addMonth:function(a){a=N+(a||1);var b=(new Date(L,a+1,0)).getDate();return this.setValue(L,a,M<=b?M:b)},addYear:function(a){return this.setValue(L+(a||1),N,M)},destroy:function(){e.add(document).off("click.d keydown.d");x.add(y).remove();
e.removeData("dateinput").removeClass(p.input);P&&e.replaceWith(P)},hide:function(b){if(G){b=a.Event();b.type="onHide";O.trigger(b);if(b.isDefaultPrevented())return;a(document).off("click.d keydown.d");x.hide();G=!1}return l},toggle:function(){return l.isOpen()?l.hide():l.show()},getConf:function(){return h},getInput:function(){return e},getCalendar:function(){return x},getValue:function(a){return a?f(h.formatter,D,a,h.lang):D},isOpen:function(){return G}});a.each(["onBeforeShow","onShow","change",
"onHide"],function(b,c){if(a.isFunction(h[c]))a(l).on(c,h[c]);l[c]=function(b){if(b)a(l).on(c,b);return l}});h.editable||e.on("focus.d click.d",l.show).keydown(function(b){var c=b.keyCode;if(!G&&0<=a(r).index(c))return l.show(b),b.preventDefault();8!=c&&46!=c||e.val("");return b.shiftKey||b.ctrlKey||b.altKey||9==c?!0:b.preventDefault()});b(e.val())&&k(D,h)}a.tools=a.tools||{version:"1.2.8-dev"};var g=[],h={},k,r=[75,76,38,39,74,72,40,37],m={};k=a.tools.dateinput={conf:{format:"mm/dd/yy",formatter:"default",
selectors:!1,yearRange:[-5,5],lang:"en",offset:[0,0],speed:0,firstDay:0,min:v,max:v,trigger:0,toggle:0,editable:0,css:{prefix:"cal",input:"date",root:0,head:0,title:0,prev:0,next:0,month:0,year:0,days:0,body:0,weeks:0,today:0,current:0,week:0,off:0,sunday:0,focus:0,disabled:0,trigger:0}},addFormatter:function(a,b){h[a]=b},localize:function(b,c){a.each(c,function(a,b){c[a]=b.split(",")});m[b]=c}};k.localize("en",{months:"January,February,March,April,May,June,July,August,September,October,November,December",
shortMonths:"Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec",days:"Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday",shortDays:"Sun,Mon,Tue,Wed,Thu,Fri,Sat"});var q=a("<a/>");k.addFormatter("default",function(a,b,c,d){return a.replace(/d{1,4}|m{1,4}|yy(?:yy)?|"[^"]*"|'[^']*'/g,function(a){return a in c?c[a]:a})});k.addFormatter("prefixed",function(a,b,c,d){return a.replace(/%(d{1,4}|m{1,4}|yy(?:yy)?|"[^"]*"|'[^']*')/g,function(a,b){return b in c?c[b]:a})});a.expr[":"].date=function(b){var c=
b.getAttribute("type");return c&&"date"==c||!!a(b).data("dateinput")};a.fn.dateinput=function(b){if(this.data("dateinput"))return this;b=a.extend(!0,{},k.conf,b);a.each(b.css,function(a,c){c||"prefix"==a||(b.css[a]=(b.css.prefix||"")+(c||a))});var c;this.each(function(){var d=new e(a(this),b);g.push(d);d=d.getInput().data("dateinput",d);c=c?c.add(d):d});return c?c:this}})(jQuery);
(function(a){function v(d,c){var b=this,e=d.add(b),g=a(window),h,k,r,m=a.tools.expose&&(c.mask||c.expose),q=Math.random().toString().slice(10);m&&("string"==typeof m&&(m={color:m}),m.closeOnClick=m.closeOnEsc=!1);var t=c.target||d.attr("rel");k=t?a(t):d;if(!k.length)throw"Could not find Overlay: "+t;if(d&&-1==d.index(k))d.off("click.jqtoverlay").on("click.jqtoverlay",function(a){if(d.attr("href")&&(a.ctrlKey||a.shiftKey||a.altKey||1<a.which))return!0;b.load(a);return a.preventDefault()});a.extend(b,
{load:function(d){if(b.isOpened())return b;var h=f[c.effect];if(!h)throw'Overlay: cannot find effect : "'+c.effect+'"';c.oneInstance&&a.each(n,function(){this.close(d)});d=d||a.Event();d.type="onBeforeLoad";e.trigger(d);if(d.isDefaultPrevented())return b;r=!0;m&&a(k).expose(m);var w=c.top,l=c.left,t=k.outerWidth(!0),u=k.outerHeight(!0);"string"==typeof w&&(w="center"==w?Math.max((g.height()-u)/2,0):parseInt(w,10)/100*g.height());"center"==l&&(l=Math.max((g.width()-t)/2,0));h[0].call(b,{top:w,left:l},
function(){r&&(d.type="onLoad",e.trigger(d))});if(m&&c.closeOnClick)a.mask.getMask().one("click",b.close);if(c.closeOnClick)a(document).on("click."+q,function(c){a(c.target).parents(k).length||b.close(c)});if(c.closeOnEsc)a(document).on("keydown."+q,function(a){27==a.keyCode&&b.close(a)});return b},close:function(d){if(!b.isOpened())return b;d=a.Event();d.type="onBeforeClose";e.trigger(d);if(!d.isDefaultPrevented())return r=!1,f[c.effect][1].call(b,function(){d.type="onClose";e.trigger(d)}),a(document).off("click."+
q+" keydown."+q),m&&a.mask.close(),b},getOverlay:function(){return k},getTrigger:function(){return d},getClosers:function(){return h},isOpened:function(){return r},getConf:function(){return c}});a.each(["onBeforeLoad","onStart","onLoad","onBeforeClose","onClose"],function(d,g){if(a.isFunction(c[g]))a(b).on(g,c[g]);b[g]=function(c){if(c)a(b).on(g,c);return b}});h=k.find(c.close||".close");h.length||c.close||(h=a('<a class="close"></a>'),k.prepend(h));h.click(function(a){b.close(a)});c.load&&b.load()}
a.tools=a.tools||{version:"1.2.8-dev"};a.tools.overlay={addEffect:function(a,c,b){f[a]=[c,b]},conf:{close:null,closeOnClick:!0,closeOnEsc:!0,closeSpeed:"fast",effect:"default",fixed:!a.browser.msie||6<a.browser.version,left:"center",load:!1,mask:null,oneInstance:!0,speed:"normal",target:null,top:"10%"}};var n=[],f={};a.tools.overlay.addEffect("default",function(d,c){var b=this.getConf(),e=a(window);b.fixed||(d.top+=e.scrollTop(),d.left+=e.scrollLeft());d.position=b.fixed?"fixed":"absolute";this.getOverlay().css(d).fadeIn(b.speed,
c)},function(a){this.getOverlay().fadeOut(this.getConf().closeSpeed,a)});a.fn.overlay=function(d){var c=this.data("overlay");if(c)return c;a.isFunction(d)&&(d={onBeforeLoad:d});d=a.extend(!0,{},a.tools.overlay.conf,d);this.each(function(){c=new v(a(this),d);n.push(c);a(this).data("overlay",c)});return d.api?c:this}})(jQuery);
(function(a){function v(a){var c=a.offset();return{top:c.top+a.height()/2,left:c.left+a.width()/2}}var n=a.tools.overlay,f=a(window);a.extend(n.conf,{start:{top:null,left:null},fadeInSpeed:"fast",zIndex:9999});n.addEffect("apple",function(d,c){var b=this.getOverlay(),e=this.getConf(),g=this.getTrigger(),h=this,k=b.outerWidth(!0),r=b.data("img"),m=e.fixed?"fixed":"absolute";if(!r){r=b.css("backgroundImage");if(!r)throw"background-image CSS property not set for overlay";r=r.slice(r.indexOf("(")+1,r.indexOf(")")).replace(/\"/g,
"");b.css("backgroundImage","none");r=a('<img src="'+r+'"/>');r.css({border:0,display:"none"}).width(k);a("body").append(r);b.data("img",r)}var q=e.start.top||Math.round(f.height()/2),t=e.start.left||Math.round(f.width()/2);g&&(g=v(g),q=g.top,t=g.left);e.fixed?(q-=f.scrollTop(),t-=f.scrollLeft()):(d.top+=f.scrollTop(),d.left+=f.scrollLeft());r.css({position:"absolute",top:q,left:t,width:0,zIndex:e.zIndex}).show();d.position=m;b.css(d);r.animate({top:d.top,left:d.left,width:k},e.speed,function(){b.css("zIndex",
e.zIndex+1).fadeIn(e.fadeInSpeed,function(){h.isOpened()&&!a(this).index(b)?c.call():b.hide()})}).css("position",m)},function(d){var c=this.getOverlay().hide(),b=this.getConf(),e=this.getTrigger(),c=c.data("img"),g={top:b.start.top,left:b.start.left,width:0};e&&a.extend(g,v(e));b.fixed&&c.css({position:"absolute"}).animate({top:"+="+f.scrollTop(),left:"+="+f.scrollLeft()},0);c.animate(g,b.closeSpeed,d)})})(jQuery);
(function(a){function v(a,b){var c=Math.pow(10,b);return Math.round(a*c)/c}function n(a,b){var c=parseInt(a.css(b),10);return c?c:(c=a[0].currentStyle)&&c.width&&parseInt(c.width,10)}function f(a){return(a=a.data("events"))&&a.onSlide}function d(b,c){function d(a,e,f,l){void 0===f?f=e/C*R:l&&(f-=c.min);x&&(f=Math.round(f/x)*x);if(void 0===e||x)e=f*C/R;if(isNaN(f))return q;e=Math.max(0,Math.min(e,C));f=e/C*R;if(l||!B)f+=c.min;B&&(l?e=C-e:f=c.max-f);f=v(f,F);var k="click"==a.type;if(I&&void 0!==w&&
!k&&(a.type="onSlide",H.trigger(a,[f,e]),a.isDefaultPrevented()))return q;l=k?c.speed:0;k=k?function(){a.type="change";H.trigger(a,[f])}:null;B?(u.animate({top:e},l,k),c.progress&&p.animate({height:C-e+u.height()/2},l)):(u.animate({left:e},l,k),c.progress&&p.animate({width:e+u.width()/2},l));w=f;b.val(f);return q}function e(){(B=c.vertical||n(z,"height")>n(z,"width"))?(C=n(z,"height")-n(u,"height"),l=z.offset().top+C):(C=n(z,"width")-n(u,"width"),l=z.offset().left)}function m(){e();q.setValue(void 0!==
c.value?c.value:c.min)}var q=this,t=c.css,z=a("<div><div/><a href='#'/></div>").data("rangeinput",q),B,w,l,C;b.before(z);var u=z.addClass(t.slider).find("a").addClass(t.handle),p=z.find("div").addClass(t.progress);a.each(["min","max","step","value"],function(a,d){var e=b.attr(d);parseFloat(e)&&(c[d]=parseFloat(e,10))});var R=c.max-c.min,x="any"==c.step?0:c.step,F=c.precision;void 0===F&&(F=x.toString().split("."),F=2===F.length?F[1].length:0);if("range"==b.attr("type")){var y=b.clone().wrap("<div/>").parent().html(),
y=a(y.replace(/type/i,"type=text data-orig-type"));y.val(c.value);b.replaceWith(y);b=y}b.addClass(t.input);var H=a(q).add(b),I=!0;a.extend(q,{getValue:function(){return w},setValue:function(b,c){e();return d(c||a.Event("api"),void 0,b,!0)},getConf:function(){return c},getProgress:function(){return p},getHandle:function(){return u},getInput:function(){return b},step:function(b,d){d=d||a.Event();q.setValue(w+("any"==c.step?1:c.step)*(b||1),d)},stepUp:function(a){return q.step(a||1)},stepDown:function(a){return q.step(-a||
-1)}});a.each(["onSlide","change"],function(b,d){if(a.isFunction(c[d]))a(q).on(d,c[d]);q[d]=function(b){if(b)a(q).on(d,b);return q}});u.drag({drag:!1}).on("dragStart",function(){e();I=f(a(q))||f(b)}).on("drag",function(a,c,e){if(b.is(":disabled"))return!1;d(a,B?c:e)}).on("dragEnd",function(a){a.isDefaultPrevented()||(a.type="change",H.trigger(a,[w]))}).click(function(a){return a.preventDefault()});z.click(function(a){if(b.is(":disabled")||a.target==u[0])return a.preventDefault();e();var c=B?u.height()/
2:u.width()/2;d(a,B?C-l-c+a.pageY:a.pageX-l-c)});c.keyboard&&b.keydown(function(c){if(!b.attr("readonly")){var d=c.keyCode,e=-1!=a([75,76,38,33,39]).index(d),h=-1!=a([74,72,40,34,37]).index(d);if((e||h)&&!(c.shiftKey||c.altKey||c.ctrlKey))return e?q.step(33==d?10:1,c):h&&q.step(34==d?-10:-1,c),c.preventDefault()}});b.blur(function(b){var c=a(this).val();c!==w&&q.setValue(c,b)});a.extend(b[0],{stepUp:q.stepUp,stepDown:q.stepDown});m();C||a(window).load(m)}a.tools=a.tools||{version:"1.2.8-dev"};var c;
c=a.tools.rangeinput={conf:{min:0,max:100,step:"any",steps:0,value:0,precision:void 0,vertical:0,keyboard:!0,progress:!1,speed:100,css:{input:"range",slider:"slider",progress:"progress",handle:"handle"}}};var b,e;a.fn.drag=function(c){document.ondragstart=function(){return!1};c=a.extend({x:!0,y:!0,drag:!0},c);b=b||a(document).on("mousedown mouseup",function(d){var f=a(d.target);if("mousedown"==d.type&&f.data("drag")){var r=f.position(),m=d.pageX-r.left,q=d.pageY-r.top,t=!0;b.on("mousemove.drag",function(a){var b=
a.pageX-m;a=a.pageY-q;var d={};c.x&&(d.left=b);c.y&&(d.top=a);t&&(f.trigger("dragStart"),t=!1);c.drag&&f.css(d);f.trigger("drag",[a,b]);e=f});d.preventDefault()}else try{e&&e.trigger("dragEnd")}finally{b.off("mousemove.drag"),e=null}});return this.data("drag",!0)};a.expr[":"].range=function(b){var c=b.getAttribute("type");return c&&"range"==c||!!a(b).filter("input").data("rangeinput")};a.fn.rangeinput=function(b){if(this.data("rangeinput"))return this;b=a.extend(!0,{},c.conf,b);var e;this.each(function(){var c=
new d(a(this),a.extend(!0,{},b)),c=c.getInput().data("rangeinput",c);e=e?e.add(c):c});return e?e:this}})(jQuery);
(function(a){function v(d,c){var b=a(c);return 2>b.length?b:d.parent().find(c)}function n(d,c){var b=this,e=d.add(b),g=d.children(),h=0,k=c.vertical;f||(f=b);1<g.length&&(g=a(c.items,d));1<c.size&&(c.circular=!1);a.extend(b,{getConf:function(){return c},getIndex:function(){return h},getSize:function(){return b.getItems().size()},getNaviButtons:function(){return q.add(t)},getRoot:function(){return d},getItemWrap:function(){return g},getItems:function(){return g.find(c.item).not("."+c.clonedClass)},
getCircularClones:function(){return g.find(c.item).filter("."+c.clonedClass)},move:function(a,c){return b.seekTo(h+a,c)},next:function(a){return b.move(c.size,a)},prev:function(a){return b.move(-c.size,a)},begin:function(a){return b.seekTo(0,a)},end:function(a){return b.seekTo(b.getSize()-1,a)},focus:function(){return f=b},addItem:function(d){d=a(d);c.circular?(g.children().last().before(d),b.getCircularClones().first().replaceWith(d.clone().addClass(c.clonedClass))):(g.append(d),t.removeClass("disabled"));
e.trigger("onAddItem",[d]);return b},removeItem:function(a){e.trigger("onRemoveItem",[a]);var d=b.getItems(),g;a.jquery?b.getItems().index(g):(g=1*a,a=b.getItems().eq(g));c.circular?(a.remove(),d=b.getItems(),a=b.getCircularClones(),a.first().replaceWith(d.last().clone().addClass("cloned")),a.last().replaceWith(d.first().clone().addClass("cloned"))):(a.remove(),b.getItems());h>=b.getSize()&&(--h,b.move(1));return b},seekTo:function(d,l,q){d.jquery||(d*=1);if(c.circular&&0===d&&-1==h&&0!==l||!c.circular&&
0>d||d>b.getSize()||-1>d)return b;var m=d;d.jquery?d=b.getItems().index(d):m=b.getItems().eq(d);var t=a.Event("onBeforeSeek");if(!q&&(e.trigger(t,[d,l]),t.isDefaultPrevented()||!m.length))return b;m=k?{top:-m.position().top}:{left:-m.position().left};h=d;f=b;void 0===l&&(l=c.speed);g.animate(m,l,c.easing,q||function(){e.trigger("onSeek",[d])});return b}});a.each(["onBeforeSeek","onSeek","onAddItem","onRemoveItem"],function(d,e){if(a.isFunction(c[e]))a(b).on(e,c[e]);b[e]=function(c){if(c)a(b).on(e,
c);return b}});if(c.circular){var r=b.getItems().slice(-1).clone().prependTo(g),m=b.getItems().eq(1).clone().appendTo(g);r.add(m).addClass(c.clonedClass);b.onBeforeSeek(function(a,c,d){if(!a.isDefaultPrevented()){var e=b.getCircularClones();if(-1==c)return b.seekTo(e.first(),d,function(){b.end(0)}),a.preventDefault();c==b.getSize()&&b.seekTo(e.last(),d,function(){b.begin(0)})}});r=d.parents().add(d).filter(function(){if("none"===a(this).css("display"))return!0});r.length?(r.show(),b.seekTo(0,0,function(){}),
r.hide()):b.seekTo(0,0,function(){})}var q=v(d,c.prev).click(function(a){a.stopPropagation();b.prev()}),t=v(d,c.next).click(function(a){a.stopPropagation();b.next()});c.circular||(b.onBeforeSeek(function(a,d){setTimeout(function(){a.isDefaultPrevented()||(q.toggleClass(c.disabledClass,0>=d),t.toggleClass(c.disabledClass,d>=b.getSize()-1))},1)}),c.initialIndex||q.addClass(c.disabledClass));2>b.getSize()&&q.add(t).addClass(c.disabledClass);c.mousewheel&&a.fn.mousewheel&&d.mousewheel(function(a,d){if(c.mousewheel)return b.move(0>
d?1:-1,c.wheelSpeed||50),!1});if(c.touch){var n,B;g[0].ontouchstart=function(a){a=a.touches[0];n=a.clientX;B=a.clientY};g[0].ontouchmove=function(a){if(1==a.touches.length&&!g.is(":animated")){var c=a.touches[0],d=n-c.clientX,c=B-c.clientY;b[k&&0<c||!k&&0<d?"next":"prev"]();a.preventDefault()}}}if(c.keyboard)a(document).on("keydown.scrollable",function(d){if(!(!c.keyboard||d.altKey||d.ctrlKey||d.metaKey||a(d.target).is(":input")||"static"!=c.keyboard&&f!=b)){var e=d.keyCode;if(k&&(38==e||40==e))return b.move(38==
e?-1:1),d.preventDefault();if(!k&&(37==e||39==e))return b.move(37==e?-1:1),d.preventDefault()}});c.initialIndex&&b.seekTo(c.initialIndex,0,function(){})}a.tools=a.tools||{version:"1.2.8-dev"};a.tools.scrollable={conf:{activeClass:"active",circular:!1,clonedClass:"cloned",disabledClass:"disabled",easing:"swing",initialIndex:0,item:"> *",items:".items",keyboard:!0,mousewheel:!1,next:".next",prev:".prev",size:1,speed:400,vertical:!1,touch:!0,wheelSpeed:0}};var f;a.fn.scrollable=function(d){var c=this.data("scrollable");
if(c)return c;d=a.extend({},a.tools.scrollable.conf,d);this.each(function(){c=new n(a(this),d);a(this).data("scrollable",c)});return d.api?c:this}})(jQuery);
(function(a){var v=a.tools.scrollable;v.autoscroll={conf:{autoplay:!0,interval:3E3,autopause:!0}};a.fn.autoscroll=function(n){"number"==typeof n&&(n={interval:n});var f=a.extend({},v.autoscroll.conf,n),d;this.each(function(){function c(){g&&clearTimeout(g);g=setTimeout(function(){b.next()},f.interval)}var b=a(this).data("scrollable"),e=b.getRoot(),g,h=!1;b&&(d=b);b.play=function(){g||(h=!1,e.on("onSeek",c),c())};b.hoverPlay=function(){h||b.play()};b.pause=function(){g=clearTimeout(g);e.off("onSeek",
c)};b.resume=function(){h||b.play()};b.stop=function(){h=!0;b.pause()};f.autopause&&e.add(b.getNaviButtons()).hover(b.pause,b.resume);f.autoplay&&b.play();b.onRemoveItem(function(a,c){2>=b.getSize()&&b.stop()})});return f.api?d:this}})(jQuery);
(function(a){function v(f,d){var c=a(d);return 2>c.length?c:f.parent().find(d)}var n=a.tools.scrollable;n.navigator={conf:{navi:".navi",naviItem:null,activeClass:"active",indexed:!1,idPrefix:null,history:!1}};a.fn.navigator=function(f){"string"==typeof f&&(f={navi:f});f=a.extend({},n.navigator.conf,f);var d;this.each(function(){function c(){return g.find(f.naviItem||"> *")}function b(b){var c=a("<"+(f.naviItem||"a")+"/>").click(function(c){a(this);e.seekTo(b);c.preventDefault();n&&history.pushState({i:b},
"")});0===b&&c.addClass(k);f.indexed&&c.text(b+1);f.idPrefix&&c.attr("id",f.idPrefix+b);return c.appendTo(g)}var e=a(this).data("scrollable"),g=f.navi.jquery?f.navi:v(e.getRoot(),f.navi),h=e.getNaviButtons(),k=f.activeClass,n=f.history&&!!history.pushState,m=e.getConf().size;e&&(d=e);e.getNaviButtons=function(){return h.add(g)};n&&(history.pushState({i:0},""),a(window).on("popstate",function(a){(a=a.originalEvent.state)&&e.seekTo(a.i)}));c().length?c().click(function(b){a(this);var d=c().index(this);
e.seekTo(d);b.preventDefault();n&&history.pushState({i:d},"")}):a.each(e.getItems(),function(a){0==a%m&&b(a)});e.onBeforeSeek(function(a,b){setTimeout(function(){if(!a.isDefaultPrevented()){var d=b/m;c().eq(d).length&&c().removeClass(k).eq(d).addClass(k)}},1)});e.onAddItem(function(a,c){var d=e.getItems().index(c);0==d%m&&b(d)});e.onRemoveItem(function(a,b){var d=e.getItems().index(b);c().eq(d).remove();c().removeClass(k).eq(d<e.getSize()-1?d:0).addClass(k)})});return f.api?d:this}})(jQuery);
(function(a){function v(c,b,d){var g=this,f=c.add(this),k=c.find(d.tabs),r=b.jquery?b:c.children(b),m;k.length||(k=c.children());r.length||(r=c.parent().find(b));r.length||(r=a(b));a.extend(this,{click:function(b,t){var r=k.eq(b),v=!c.data("tabs");"string"==typeof b&&b.replace("#","")&&(r=k.filter('[href*="'+b.replace("#","")+'"]'),b=Math.max(k.index(r),0));if(d.rotate){var w=k.length-1;if(0>b)return g.click(w,t);if(b>w)return g.click(0,t)}if(!r.length){if(0<=m)return g;b=d.initialIndex;r=k.eq(b)}if(b===
m)return g;t=t||a.Event();t.type="onBeforeClick";f.trigger(t,[b]);if(!t.isDefaultPrevented())return n[v?d.initialEffect&&d.effect||"default":d.effect].call(g,b,function(){m=b;t.type="onClick";f.trigger(t,[b])}),k.removeClass(d.current),r.addClass(d.current),g},getConf:function(){return d},getTabs:function(){return k},getPanes:function(){return r},getCurrentPane:function(){return r.eq(m)},getCurrentTab:function(){return k.eq(m)},getIndex:function(){return m},next:function(){return g.click(m+1)},prev:function(){return g.click(m-
1)},destroy:function(){k.off(d.event).removeClass(d.current);r.find('a[href^="#"]').off("click.T");return g}});a.each(["onBeforeClick","onClick"],function(b,c){if(a.isFunction(d[c]))a(g).on(c,d[c]);g[c]=function(b){if(b)a(g).on(c,b);return g}});d.history&&a.fn.history&&(a.tools.history.init(k),d.event="history");k.each(function(b){a(this).on(d.event,function(a){g.click(b,a);return a.preventDefault()})});r.find('a[href^="#"]').on("click.T",function(b){g.click(a(this).attr("href"),b)});location.hash&&
"a"==d.tabs&&c.find('[href="'+location.hash.replace('"',"")+'"]').length?g.click(location.hash):(0===d.initialIndex||0<d.initialIndex)&&g.click(d.initialIndex)}a.tools=a.tools||{version:"1.2.8-dev"};a.tools.tabs={conf:{tabs:"a",current:"current",onBeforeClick:null,onClick:null,effect:"default",initialEffect:!1,initialIndex:0,event:"click",rotate:!1,slideUpSpeed:400,slideDownSpeed:400,history:!1},addEffect:function(a,b){n[a]=b}};var n={"default":function(a,b){this.getPanes().hide().eq(a).show();b.call()},
fade:function(a,b){var d=this.getConf(),g=d.fadeOutSpeed,f=this.getPanes();g?f.fadeOut(g):f.hide();f.eq(a).fadeIn(d.fadeInSpeed,b)},slide:function(a,b){var d=this.getConf();this.getPanes().slideUp(d.slideUpSpeed);this.getPanes().eq(a).slideDown(d.slideDownSpeed,b)},ajax:function(a,b){this.getPanes().eq(0).load(this.getTabs().eq(a).attr("href"),b)}},f,d;a.tools.tabs.addEffect("horizontal",function(c,b){if(!f){var e=this.getPanes().eq(c),g=this.getCurrentPane();d||(d=this.getPanes().eq(0).width());
f=!0;e.show();g.animate({width:0},{step:function(a){e.css("width",d-a)},complete:function(){a(this).hide();b.call();f=!1}});g.length||(b.call(),f=!1)}});a.fn.tabs=function(c,b){var d=this.data("tabs");d&&(d.destroy(),this.removeData("tabs"));a.isFunction(b)&&(b={onBeforeClick:b});b=a.extend({},a.tools.tabs.conf,b);this.each(function(){d=new v(a(this),c,b);a(this).data("tabs",d)});return b.api?d:this}})(jQuery);
(function(a){function v(){if(a.browser.msie){var b=a(document).height(),c=a(window).height();return[window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,20>b-c?c:b]}return[a(document).width(),a(document).height()]}function n(b){if(b)return b.call(a.mask)}a.tools=a.tools||{version:"1.2.8-dev"};var f;f=a.tools.expose={conf:{maskId:"exposeMask",loadSpeed:"slow",closeSpeed:"fast",closeOnClick:!0,closeOnEsc:!0,zIndex:9998,opacity:.8,startOpacity:0,color:"#fff",onLoad:null,
onClose:null}};var d,c,b,e,g;a.mask={load:function(h,k){if(b)return this;"string"==typeof h&&(h={color:h});h=h||e;e=h=a.extend(a.extend({},f.conf),h);d=a("#"+h.maskId);d.length||(d=a("<div/>").attr("id",h.maskId),a("body").append(d));var r=v();d.css({position:"absolute",top:0,left:0,width:r[0],height:r[1],display:"none",opacity:h.startOpacity,zIndex:h.zIndex});h.color&&d.css("backgroundColor",h.color);if(!1===n(h.onBeforeLoad))return this;if(h.closeOnEsc)a(document).on("keydown.mask",function(b){27==
b.keyCode&&a.mask.close(b)});if(h.closeOnClick)d.on("click.mask",function(b){a.mask.close(b)});a(window).on("resize.mask",function(){a.mask.fit()});k&&k.length&&(g=k.eq(0).css("zIndex"),a.each(k,function(){var b=a(this);/relative|absolute|fixed/i.test(b.css("position"))||b.css("position","relative")}),c=k.css({zIndex:Math.max(h.zIndex+1,"auto"==g?0:g)}));d.css({display:"block"}).fadeTo(h.loadSpeed,h.opacity,function(){a.mask.fit();n(h.onLoad);b="full"});b=!0;return this},close:function(){if(b){if(!1===
n(e.onBeforeClose))return this;d.fadeOut(e.closeSpeed,function(){c&&c.css({zIndex:g});b=!1;n(e.onClose)});a(document).off("keydown.mask");d.off("click.mask");a(window).off("resize.mask")}return this},fit:function(){if(b){var a=d.css("display");d.css("display","none");var c=v();d.css({display:a,width:c[0],height:c[1]})}},getMask:function(){return d},isLoaded:function(a){return a?"full"==b:b},getConf:function(){return e},getExposed:function(){return c}};a.fn.mask=function(b){a.mask.load(b);return this};
a.fn.expose=function(b){a.mask.load(b,this);return this}})(jQuery);
(function(a){function v(a){if(a){var c=f.contentWindow.document;c.open().close();c.location.hash=a}}var n,f,d,c;a.tools=a.tools||{version:"1.2.8-dev"};a.tools.history={init:function(b){c||(a.browser.msie&&"8">a.browser.version?f||(f=a("<iframe/>").attr("src","javascript:false;").hide().get(0),a("body").append(f),setInterval(function(){var b=f.contentWindow.document.location.hash;n!==b&&a(window).trigger("hash",b)},100),v(location.hash||"#")):setInterval(function(){var b=location.hash;b!==n&&a(window).trigger("hash",
b)},100),d=d?d.add(b):b,b.click(function(b){var c=a(this).attr("href");f&&v(c);if("#"!=c.slice(0,1))return location.href="#"+c,b.preventDefault()}),c=!0)}};a(window).on("hash",function(b,c){c?d.filter(function(){var b=a(this).attr("href");return b==c||b==c.replace("#","")}).trigger("history",[c]):d.eq(0).trigger("history",[c]);n=c});a.fn.history=function(b){a.tools.history.init(this);return this.on("history",b)}})(jQuery);
(function(a){function v(f){switch(f.type){case "mousemove":return a.extend(f.data,{clientX:f.clientX,clientY:f.clientY,pageX:f.pageX,pageY:f.pageY});case "DOMMouseScroll":a.extend(f,f.data);f.delta=-f.detail/3;break;case "mousewheel":f.delta=f.wheelDelta/120}f.type="wheel";return a.event.handle.call(this,f,f.delta)}a.fn.mousewheel=function(a){return this[a?"on":"trigger"]("wheel",a)};a.event.special.wheel={setup:function(){a.event.add(this,n,v,{})},teardown:function(){a.event.remove(this,n,v)}};var n=
a.browser.mozilla?"DOMMouseScroll"+("1.9">a.browser.version?" mousemove":""):"mousewheel"})(jQuery);
(function(a){function v(d,c,b){var e=b.relative?d.position().top:d.offset().top,g=b.relative?d.position().left:d.offset().left,f=b.position[0],e=e-(c.outerHeight()-b.offset[0]),g=g+(d.outerWidth()+b.offset[1]);/iPad/i.test(navigator.userAgent)&&(e-=a(window).scrollTop());var k=c.outerHeight()+d.outerHeight();"center"==f&&(e+=k/2);"bottom"==f&&(e+=k);f=b.position[1];d=c.outerWidth()+d.outerWidth();"center"==f&&(g-=d/2);"left"==f&&(g-=d);return{top:e,left:g}}function n(d,c){var b=this,e=d.add(b),g,
h=0,k=0,n=d.attr("title"),m=d.attr("data-tooltip"),q=f[c.effect],t,z=d.is(":input"),B=z&&d.is(":checkbox, :radio, select, :button, :submit"),w=d.attr("type"),l=c.events[w]||c.events[z?B?"widget":"input":"def"];if(!q)throw'Nonexistent effect "'+c.effect+'"';l=l.split(/,\s*/);if(2!=l.length)throw"Tooltip: bad events configuration for "+w;d.on(l[0],function(a){clearTimeout(h);c.predelay?k=setTimeout(function(){b.show(a)},c.predelay):b.show(a)}).on(l[1],function(a){clearTimeout(k);c.delay?h=setTimeout(function(){b.hide(a)},
c.delay):b.hide(a)});n&&c.cancelDefault&&(d.removeAttr("title"),d.data("title",n));a.extend(b,{show:function(f){if(!g&&(m?g=a(m):c.tip?g=a(c.tip).eq(0):n?g=a(c.layout).addClass(c.tipClass).appendTo(document.body).hide().append(n):(g=d.find("."+c.tipClass),g.length||(g=d.next()),g.length||(g=d.parent().next())),!g.length))throw"Cannot find tooltip for "+d;if(b.isShown())return b;g.stop(!0,!0);var u=v(d,g,c);c.tip&&g.html(d.data("title"));f=a.Event();f.type="onBeforeShow";e.trigger(f,[u]);if(f.isDefaultPrevented())return b;
u=v(d,g,c);g.css({position:"absolute",top:u.top,left:u.left});t=!0;q[0].call(b,function(){f.type="onShow";t="full";e.trigger(f)});u=c.events.tooltip.split(/,\s*/);if(!g.data("__set")){g.off(u[0]).on(u[0],function(){clearTimeout(h);clearTimeout(k)});if(u[1]&&!d.is("input:not(:checkbox, :radio), textarea"))g.off(u[1]).on(u[1],function(a){a.relatedTarget!=d[0]&&d.trigger(l[1].split(" ")[0])});c.tip||g.data("__set",!0)}return b},hide:function(d){if(!g||!b.isShown())return b;d=a.Event();d.type="onBeforeHide";
e.trigger(d);if(!d.isDefaultPrevented())return t=!1,f[c.effect][1].call(b,function(){d.type="onHide";e.trigger(d)}),b},isShown:function(a){return a?"full"==t:t},getConf:function(){return c},getTip:function(){return g},getTrigger:function(){return d}});a.each(["onHide","onBeforeShow","onShow","onBeforeHide"],function(d,e){if(a.isFunction(c[e]))a(b).on(e,c[e]);b[e]=function(c){if(c)a(b).on(e,c);return b}})}a.tools=a.tools||{version:"1.2.8-dev"};a.tools.tooltip={conf:{effect:"toggle",fadeOutSpeed:"fast",
predelay:0,delay:30,opacity:1,tip:0,fadeIE:!1,position:["top","center"],offset:[0,0],relative:!1,cancelDefault:!0,events:{def:"mouseenter,mouseleave",input:"focus,blur",widget:"focus mouseenter,blur mouseleave",tooltip:"mouseenter,mouseleave"},layout:"<div/>",tipClass:"tooltip"},addEffect:function(a,c,b){f[a]=[c,b]}};var f={toggle:[function(a){var c=this.getConf(),b=this.getTip(),c=c.opacity;1>c&&b.css({opacity:c});b.show();a.call()},function(a){this.getTip().hide();a.call()}],fade:[function(d){var c=
this.getConf();!a.browser.msie||c.fadeIE?this.getTip().fadeTo(c.fadeInSpeed,c.opacity,d):(this.getTip().show(),d())},function(d){var c=this.getConf();!a.browser.msie||c.fadeIE?this.getTip().fadeOut(c.fadeOutSpeed,d):(this.getTip().hide(),d())}]};a.fn.tooltip=function(d){d=a.extend(!0,{},a.tools.tooltip.conf,d);"string"==typeof d.position&&(d.position=d.position.split(/,?\s/));this.each(function(){a(this).data("tooltip")||(api=new n(a(this),d),a(this).data("tooltip",api))});return d.api?api:this}})(jQuery);
(function(a){var v=a.tools.tooltip;a.extend(v.conf,{direction:"up",bounce:!1,slideOffset:10,slideInSpeed:200,slideOutSpeed:200,slideFade:!a.browser.msie});var n={up:["-","top"],down:["+","top"],left:["-","left"],right:["+","left"]};v.addEffect("slide",function(a){var d=this.getConf(),c=this.getTip(),b=d.slideFade?{opacity:d.opacity}:{},e=n[d.direction]||n.up;b[e[1]]=e[0]+"="+d.slideOffset;d.slideFade&&c.css({opacity:0});c.show().animate(b,d.slideInSpeed,a)},function(f){var d=this.getConf(),c=d.slideOffset,
b=d.slideFade?{opacity:0}:{},e=n[d.direction]||n.up,g=""+e[0];d.bounce&&(g="+"==g?"-":"+");b[e[1]]=g+"="+c;this.getTip().animate(b,d.slideOutSpeed,function(){a(this).hide();f.call()})})})(jQuery);

/**
 * jQuery Easing v1.3 - http://gsgd.co.uk/sandbox/jquery/easing/
 *
 * Uses the built in easing capabilities added In jQuery 1.1
 * to offer multiple easing options
 *
 * TERMS OF USE - jQuery Easing
 *
 * Open source under the BSD License.
 *
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 *
 * TERMS OF USE - EASING EQUATIONS
 *
 * Open source under the BSD License.
 *
 * Copyright © 2001 Robert Penner
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list
 * of conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 *
 * Neither the name of the author nor the names of contributors may be used to endorse
 * or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
*/
jQuery.easing.jswing=jQuery.easing.swing;jQuery.extend(jQuery.easing,{def:"easeOutQuad",swing:function(e,f,a,h,g){return jQuery.easing[jQuery.easing.def](e,f,a,h,g)},easeInQuad:function(e,f,a,h,g){return h*(f/=g)*f+a},easeOutQuad:function(e,f,a,h,g){return -h*(f/=g)*(f-2)+a},easeInOutQuad:function(e,f,a,h,g){if((f/=g/2)<1){return h/2*f*f+a}return -h/2*((--f)*(f-2)-1)+a},easeInCubic:function(e,f,a,h,g){return h*(f/=g)*f*f+a},easeOutCubic:function(e,f,a,h,g){return h*((f=f/g-1)*f*f+1)+a},easeInOutCubic:function(e,f,a,h,g){if((f/=g/2)<1){return h/2*f*f*f+a}return h/2*((f-=2)*f*f+2)+a},easeInQuart:function(e,f,a,h,g){return h*(f/=g)*f*f*f+a},easeOutQuart:function(e,f,a,h,g){return -h*((f=f/g-1)*f*f*f-1)+a},easeInOutQuart:function(e,f,a,h,g){if((f/=g/2)<1){return h/2*f*f*f*f+a}return -h/2*((f-=2)*f*f*f-2)+a},easeInQuint:function(e,f,a,h,g){return h*(f/=g)*f*f*f*f+a},easeOutQuint:function(e,f,a,h,g){return h*((f=f/g-1)*f*f*f*f+1)+a},easeInOutQuint:function(e,f,a,h,g){if((f/=g/2)<1){return h/2*f*f*f*f*f+a}return h/2*((f-=2)*f*f*f*f+2)+a},easeInSine:function(e,f,a,h,g){return -h*Math.cos(f/g*(Math.PI/2))+h+a},easeOutSine:function(e,f,a,h,g){return h*Math.sin(f/g*(Math.PI/2))+a},easeInOutSine:function(e,f,a,h,g){return -h/2*(Math.cos(Math.PI*f/g)-1)+a},easeInExpo:function(e,f,a,h,g){return(f==0)?a:h*Math.pow(2,10*(f/g-1))+a},easeOutExpo:function(e,f,a,h,g){return(f==g)?a+h:h*(-Math.pow(2,-10*f/g)+1)+a},easeInOutExpo:function(e,f,a,h,g){if(f==0){return a}if(f==g){return a+h}if((f/=g/2)<1){return h/2*Math.pow(2,10*(f-1))+a}return h/2*(-Math.pow(2,-10*--f)+2)+a},easeInCirc:function(e,f,a,h,g){return -h*(Math.sqrt(1-(f/=g)*f)-1)+a},easeOutCirc:function(e,f,a,h,g){return h*Math.sqrt(1-(f=f/g-1)*f)+a},easeInOutCirc:function(e,f,a,h,g){if((f/=g/2)<1){return -h/2*(Math.sqrt(1-f*f)-1)+a}return h/2*(Math.sqrt(1-(f-=2)*f)+1)+a},easeInElastic:function(f,h,e,l,k){var i=1.70158;var j=0;var g=l;if(h==0){return e}if((h/=k)==1){return e+l}if(!j){j=k*0.3}if(g<Math.abs(l)){g=l;var i=j/4}else{var i=j/(2*Math.PI)*Math.asin(l/g)}return -(g*Math.pow(2,10*(h-=1))*Math.sin((h*k-i)*(2*Math.PI)/j))+e},easeOutElastic:function(f,h,e,l,k){var i=1.70158;var j=0;var g=l;if(h==0){return e}if((h/=k)==1){return e+l}if(!j){j=k*0.3}if(g<Math.abs(l)){g=l;var i=j/4}else{var i=j/(2*Math.PI)*Math.asin(l/g)}return g*Math.pow(2,-10*h)*Math.sin((h*k-i)*(2*Math.PI)/j)+l+e},easeInOutElastic:function(f,h,e,l,k){var i=1.70158;var j=0;var g=l;if(h==0){return e}if((h/=k/2)==2){return e+l}if(!j){j=k*(0.3*1.5)}if(g<Math.abs(l)){g=l;var i=j/4}else{var i=j/(2*Math.PI)*Math.asin(l/g)}if(h<1){return -0.5*(g*Math.pow(2,10*(h-=1))*Math.sin((h*k-i)*(2*Math.PI)/j))+e}return g*Math.pow(2,-10*(h-=1))*Math.sin((h*k-i)*(2*Math.PI)/j)*0.5+l+e},easeInBack:function(e,f,a,i,h,g){if(g==undefined){g=1.70158}return i*(f/=h)*f*((g+1)*f-g)+a},easeOutBack:function(e,f,a,i,h,g){if(g==undefined){g=1.70158}return i*((f=f/h-1)*f*((g+1)*f+g)+1)+a},easeInOutBack:function(e,f,a,i,h,g){if(g==undefined){g=1.70158}if((f/=h/2)<1){return i/2*(f*f*(((g*=(1.525))+1)*f-g))+a}return i/2*((f-=2)*f*(((g*=(1.525))+1)*f+g)+2)+a},easeInBounce:function(e,f,a,h,g){return h-jQuery.easing.easeOutBounce(e,g-f,0,h,g)+a},easeOutBounce:function(e,f,a,h,g){if((f/=g)<(1/2.75)){return h*(7.5625*f*f)+a}else{if(f<(2/2.75)){return h*(7.5625*(f-=(1.5/2.75))*f+0.75)+a}else{if(f<(2.5/2.75)){return h*(7.5625*(f-=(2.25/2.75))*f+0.9375)+a}else{return h*(7.5625*(f-=(2.625/2.75))*f+0.984375)+a}}}},easeInOutBounce:function(e,f,a,h,g){if(f<g/2){return jQuery.easing.easeInBounce(e,f*2,0,h,g)*0.5+a}return jQuery.easing.easeOutBounce(e,f*2-g,0,h,g)*0.5+h*0.5+a}});

/**
* hoverIntent r5 // 2007.03.27 // jQuery 1.1.2+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
*
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne <brian@cherne.net>
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY;};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev]);}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev]);};var handleHover=function(e){var p=(e.type=="mouseover"?e.fromElement:e.toElement)||e.relatedTarget;while(p&&p!=this){try{p=p.parentNode;}catch(e){p=this;}}if(p==this){return false;}var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);}if(e.type=="mouseover"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob);},cfg.timeout);}}};return this.mouseover(handleHover).mouseout(handleHover);};})(jQuery);

/*
 * jQuery Color Animations
 * Copyright 2007 John Resig
 * Released under the MIT and GPL licenses.
 */
(function(d){d.each(["backgroundColor","borderBottomColor","borderLeftColor","borderRightColor","borderTopColor","color","outlineColor"],function(f,e){d.fx.step[e]=function(g){if(g.state==0){g.start=c(g.elem,e);g.end=b(g.end)}g.elem.style[e]="rgb("+[Math.max(Math.min(parseInt((g.pos*(g.end[0]-g.start[0]))+g.start[0]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[1]-g.start[1]))+g.start[1]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[2]-g.start[2]))+g.start[2]),255),0)].join(",")+")"}});function b(f){var e;if(f&&f.constructor==Array&&f.length==3){return f}if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return[parseInt(e[1]),parseInt(e[2]),parseInt(e[3])]}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return[parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55]}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}return a[d.trim(f).toLowerCase()]}function c(g,e){var f;do{f=d.curCSS(g,e);if(f!=""&&f!="transparent"||d.nodeName(g,"body")){break}e="backgroundColor"}while(g=g.parentNode);return b(f)}var a={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0]}})(jQuery);

/**
 * @license Rangy Inputs, a jQuery plug-in for selection and caret manipulation within textareas and text inputs.
 *
 * https://github.com/timdown/rangyinputs
 *
 * For range and selection features for contenteditable, see Rangy.

 * http://code.google.com/p/rangy/
 *
 * Depends on jQuery 1.0 or later.
 *
 * Copyright 2013, Tim Down
 * Licensed under the MIT license.
 * Version: 1.1.2
 * Build date: 6 September 2013
 */
!function(a){function l(a,b){var c=typeof a[b];return"function"===c||!("object"!=c||!a[b])||"unknown"==c}function m(a,c){return typeof a[c]!=b}function n(a,b){return!("object"!=typeof a[b]||!a[b])}function o(a){window.console&&window.console.log&&window.console.log("RangyInputs not supported in your browser. Reason: "+a)}function p(a,c,d){return 0>c&&(c+=a.value.length),typeof d==b&&(d=c),0>d&&(d+=a.value.length),{start:c,end:d}}function q(a,b,c){return{start:b,end:c,length:c-b,text:a.value.slice(b,c)}}function r(){return n(document,"body")?document.body:document.getElementsByTagName("body")[0]}var c,d,e,f,g,h,i,j,k,b="undefined";a(document).ready(function(){function v(a,b){return function(){var c=this.jquery?this[0]:this,d=c.nodeName.toLowerCase();if(1==c.nodeType&&("textarea"==d||"input"==d&&"text"==c.type)){var e=[c].concat(Array.prototype.slice.call(arguments)),f=a.apply(this,e);if(!b)return f}return b?this:void 0}}var s=document.createElement("textarea");if(r().appendChild(s),m(s,"selectionStart")&&m(s,"selectionEnd"))c=function(a){var b=a.selectionStart,c=a.selectionEnd;return q(a,b,c)},d=function(a,b,c){var d=p(a,b,c);a.selectionStart=d.start,a.selectionEnd=d.end},k=function(a,b){b?a.selectionEnd=a.selectionStart:a.selectionStart=a.selectionEnd};else{if(!(l(s,"createTextRange")&&n(document,"selection")&&l(document.selection,"createRange")))return r().removeChild(s),o("No means of finding text input caret position"),void 0;c=function(a){var d,e,f,g,b=0,c=0,h=document.selection.createRange();return h&&h.parentElement()==a&&(f=a.value.length,d=a.value.replace(/\r\n/g,"\n"),e=a.createTextRange(),e.moveToBookmark(h.getBookmark()),g=a.createTextRange(),g.collapse(!1),e.compareEndPoints("StartToEnd",g)>-1?b=c=f:(b=-e.moveStart("character",-f),b+=d.slice(0,b).split("\n").length-1,e.compareEndPoints("EndToEnd",g)>-1?c=f:(c=-e.moveEnd("character",-f),c+=d.slice(0,c).split("\n").length-1))),q(a,b,c)};var t=function(a,b){return b-(a.value.slice(0,b).split("\r\n").length-1)};d=function(a,b,c){var d=p(a,b,c),e=a.createTextRange(),f=t(a,d.start);e.collapse(!0),d.start==d.end?e.move("character",f):(e.moveEnd("character",t(a,d.end)),e.moveStart("character",f)),e.select()},k=function(a,b){var c=document.selection.createRange();c.collapse(b),c.select()}}r().removeChild(s),f=function(a,b,c,e){var f;b!=c&&(f=a.value,a.value=f.slice(0,b)+f.slice(c)),e&&d(a,b,b)},e=function(a){var b=c(a);f(a,b.start,b.end,!0)},j=function(a){var e,b=c(a);return b.start!=b.end&&(e=a.value,a.value=e.slice(0,b.start)+e.slice(b.end)),d(a,b.start,b.start),b.text};var u=function(a,b,c,e){var f=b+c.length;if(e="string"==typeof e?e.toLowerCase():"",("collapsetoend"==e||"select"==e)&&/[\r\n]/.test(c)){var g=c.replace(/\r\n/g,"\n").replace(/\r/g,"\n");f=b+g.length;var h=b+g.indexOf("\n");"\r\n"==a.value.slice(h,h+2)&&(f+=g.match(/\n/g).length)}switch(e){case"collapsetostart":d(a,b,b);break;case"collapsetoend":d(a,f,f);break;case"select":d(a,b,f)}};g=function(a,b,c,d){var e=a.value;a.value=e.slice(0,c)+b+e.slice(c),"boolean"==typeof d&&(d=d?"collapseToEnd":""),u(a,c,b,d)},h=function(a,b,d){var e=c(a),f=a.value;a.value=f.slice(0,e.start)+b+f.slice(e.end),u(a,e.start,b,d||"collapseToEnd")},i=function(a,d,e,f){typeof e==b&&(e=d);var g=c(a),h=a.value;a.value=h.slice(0,g.start)+d+g.text+e+h.slice(g.end);var i=g.start+d.length;u(a,i,g.text,f||"select")},a.fn.extend({getSelection:v(c,!1),setSelection:v(d,!0),collapseSelection:v(k,!0),deleteSelectedText:v(e,!0),deleteText:v(f,!0),extractSelectedText:v(j,!1),insertText:v(g,!0),replaceSelectedText:v(h,!0),surroundSelectedText:v(i,!0)})})}(jQuery);

/*
 * XenForo xenforo.min.js
 * Copyright 2010-2016 XenForo Ltd.
 * Released under the XenForo License Agreement: http://xenforo.com/license-agreement
 */

var XenForo = {};

// _XF_JS_UNCOMPRESSED_TEST_ - do not edit/remove

if (jQuery === undefined) jQuery = $ = {};
if ($.tools === undefined) console.error('jQuery Tools is not loaded.');

/**
 * Deal with Firebug not being present
 */
!function(w) { var fn, i = 0;
	if (!w.console) w.console = {};
	if (w.console.log && !w.console.debug) w.console.debug = w.console.log;
	fn = ['assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error', 'getFirebugElement', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log', 'notifyFirebug', 'profile', 'profileEnd', 'time', 'timeEnd', 'trace', 'warn'];
	for (i = 0; i < fn.length; ++i) if (!w.console[fn[i]]) w.console[fn[i]] = function() {};
}(window);

/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	var isTouchBrowser = (function() {
		var _isTouchBrowserVal;

		try
		{
			_isTouchBrowserVal = !!('ontouchstart' in window || navigator.maxTouchPoints || navigator.msMaxTouchPoints);
		}
		catch(e)
		{
			_isTouchBrowserVal = !!(navigator.userAgent.indexOf('webOS') != -1);
		}

		return function()
		{
			return _isTouchBrowserVal;
		};
	})();

	var classes = ['hasJs'];
	classes.push(isTouchBrowser() ? 'Touch' : 'NoTouch');

	var div = document.createElement('div');
	classes.push(('draggable' in div) || ('ondragstart' in div && 'ondrop' in div) ? 'HasDragDrop' : 'NoDragDrop');

	// not especially nice but...
	if (navigator.userAgent.search(/\((iPhone|iPad|iPod);/) != -1)
	{
		classes.push('iOS');
	}

	var $html = $('html');
	$html.addClass(classes.join(' ')).removeClass('NoJs');

	/**
	 * Fix IE abbr handling
	 */
	document.createElement('abbr');

	/**
	 * Detect mobile webkit
	 */
	if (/webkit.*mobile/i.test(navigator.userAgent))
	{
		XenForo._isWebkitMobile = true;
	}

	// preserve original jQuery Tools .overlay()
	jQuery.fn._jQueryToolsOverlay = jQuery.fn.overlay;

	/**
	 * Extends jQuery core
	 */
	jQuery.extend(true,
	{
		/**
		 * Sets the context of 'this' within a called function.
		 * Takes identical parameters to $.proxy, but does not
		 * enforce the one-elment-one-method merging that $.proxy
		 * does, allowing multiple objects of the same type to
		 * bind to a single element's events (for example).
		 *
		 * @param function|object Function to be called | Context for 'this', method is a property of fn
		 * @param function|string Context for 'this' | Name of method within fn to be called
		 *
		 * @return function
		 */
		context: function(fn, context)
		{
			if (typeof context == 'string')
			{
				var _context = fn;
				fn = fn[context];
				context = _context;
			}

			return function() { return fn.apply(context, arguments); };
		},

		/**
		 * Sets a cookie.
		 *
		 * @param string cookie name (escaped)
		 * @param mixed cookie value
		 * @param string cookie expiry date
		 *
		 * @return mixed cookie value
		 */
		setCookie: function(name, value, expires)
		{
			console.log('Set cookie %s = %s', name, value);

			document.cookie = XenForo._cookieConfig.prefix + name + '=' + encodeURIComponent(value)
				+ (expires === undefined ? '' : ';expires=' + expires.toUTCString())
				+ (XenForo._cookieConfig.path  ? ';path=' + XenForo._cookieConfig.path : '')
				+ (XenForo._cookieConfig.domain ? ';domain=' + XenForo._cookieConfig.domain : '');

			return value;
		},

		/**
		 * Fetches the value of a named cookie.
		 *
		 * @param string Cookie name (escaped)
		 *
		 * @return string Cookie value
		 */
		getCookie: function(name)
		{
			var expr, cookie;

			expr = new RegExp('(^| )' + XenForo._cookieConfig.prefix + name + '=([^;]+)(;|$)');
			cookie = expr.exec(document.cookie);

			if (cookie)
			{
				return decodeURIComponent(cookie[2]);
			}
			else
			{
				return null;
			}
		},

		/**
		 * Deletes a cookie.
		 *
		 * @param string Cookie name (escaped)
		 *
		 * @return null
		 */
		deleteCookie: function(name)
		{
			console.info('Delete cookie %s', name);

			document.cookie = XenForo._cookieConfig.prefix + name + '='
				+ (XenForo._cookieConfig.path  ? '; path=' + XenForo._cookieConfig.path : '')
				+ (XenForo._cookieConfig.domain ? '; domain=' + XenForo._cookieConfig.domain : '')
				+ '; expires=Thu, 01-Jan-70 00:00:01 GMT';

			return null;
		}
	});

	/**
	 * Extends jQuery functions
	 */
	jQuery.fn.extend(
	{
		/**
		 * Wrapper for XenForo.activate, for 'this' element
		 *
		 * @return jQuery
		 */
		xfActivate: function()
		{
			return XenForo.activate(this);
		},

		/**
		 * Retuns .data(key) for this element, or the default value if there is no data
		 *
		 * @param string key
		 * @param mixed defaultValue
		 *
		 * @return mixed
		 */
		dataOrDefault: function(key, defaultValue)
		{
			var value = this.data(key);

			if (value === undefined)
			{
				return defaultValue;
			}

			return value;
		},

		/**
		 * Like .val() but also trims trailing whitespace
		 */
		strval: function()
		{
			return String(this.val()).replace(/\s+$/g, '');
		},

		/**
		 * Get the 'name' attribute of an element, or if it exists, the value of 'data-fieldName'
		 *
		 * @return string
		 */
		fieldName: function()
		{
			return this.data('fieldname') || this.attr('name');
		},

		/**
		 * Get the value that would be submitted with 'this' element's name on form submit
		 *
		 * @return string
		 */
		fieldValue: function()
		{
			switch (this.attr('type'))
			{
				case 'checkbox':
				{
					return $('input:checkbox[name="' + this.fieldName() + '"]:checked', this.context.form).val();
				}

				case 'radio':
				{
					return $('input:radio[name="' + this.fieldName() + '"]:checked', this.context.form).val();
				}

				default:
				{
					return this.val();
				}
			}
		},

		_jqSerialize : $.fn.serialize,

		/**
		 * Overridden jQuery serialize method to ensure that RTE areas are serialized properly.
		 */
		serialize: function()
		{
			$('textarea.BbCodeWysiwygEditor').each(function() {
				var data = $(this).data('XenForo.BbCodeWysiwygEditor');
				if (data)
				{
					data.syncEditor();
				}
			});

			return this._jqSerialize();
		},

		_jqSerializeArray : $.fn.serializeArray,

		/**
		 * Overridden jQuery serializeArray method to ensure that RTE areas are serialized properly.
		 */
		serializeArray: function()
		{
			$('textarea.BbCodeWysiwygEditor').each(function() {
				var data = $(this).data('XenForo.BbCodeWysiwygEditor');
				if (data)
				{
					data.syncEditor();
				}
			});

			return this._jqSerializeArray();
		},

		/**
		 * Returns the position and size of an element, including hidden elements.
		 *
		 * If the element is hidden, it will very quickly un-hides a display:none item,
		 * gets its offset and size, restore the element to its hidden state and returns values.
		 *
		 * @param string inner/outer/{none} Defines the jQuery size function to use
		 * @param string offset/position/{none} Defines the jQuery position function to use (default: offset)
		 *
		 * @return object Offset { left: float, top: float }
		 */
		coords: function(sizeFn, offsetFn)
		{
			var coords,
				visibility,
				display,
				widthFn,
				heightFn,
				hidden = this.is(':hidden');

			if (hidden)
			{
				visibility = this.css('visibility'),
				display = this.css('display');

				this.css(
				{
					visibility: 'hidden',
					display: 'block'
				});
			}

			switch (sizeFn)
			{
				case 'inner':
				{
					widthFn = 'innerWidth';
					heightFn = 'innerHeight';
					break;
				}
				case 'outer':
				{
					widthFn = 'outerWidth';
					heightFn = 'outerHeight';
					break;
				}
				default:
				{
					widthFn = 'width';
					heightFn = 'height';
				}
			}

			switch (offsetFn)
			{
				case 'position':
				{
					offsetFn = 'position';
					break;
				}

				default:
				{
					offsetFn = 'offset';
					break;
				}
			}

			coords = this[offsetFn]();
				coords.width = this[widthFn]();
				coords.height = this[heightFn]();

			if (hidden)
			{
				this.css(
				{
					display: display,
					visibility: visibility
				});
			}

			return coords;
		},

		/**
		 * Sets a unique id for an element, if one is not already present
		 */
		uniqueId: function()
		{
			if (!this.attr('id'))
			{
				this.attr('id', 'XenForoUniq' + XenForo._uniqueIdCounter++);
			}

			return this;
		},

		/**
		 * Wrapper functions for commonly-used animation effects, so we can customize their behaviour as required
		 */
		xfFadeIn: function(speed, callback)
		{
			return this.fadeIn(speed, function() { $(this).ieOpacityFix(callback); });
		},
		xfFadeOut: function(speed, callback)
		{
			return this.fadeOut(speed, callback);
		},
		xfShow: function(speed, callback)
		{
			return this.show(speed, function() { $(this).ieOpacityFix(callback); });
		},
		xfHide: function(speed, callback)
		{
			return this.hide(speed, callback);
		},
		xfSlideDown: function(speed, callback)
		{
			return this.slideDown(speed, function() { $(this).ieOpacityFix(callback); });
		},
		xfSlideUp: function(speed, callback)
		{
			return this.slideUp(speed, callback);
		},

		/**
		 * Animates an element opening a space for itself, then fading into that space
		 *
		 * @param integer|string Speed of fade-in
		 * @param function Callback function on completion
		 *
		 * @return jQuery
		 */
		xfFadeDown: function(fadeSpeed, callback)
		{
			this.filter(':hidden').xfHide().css('opacity', 0);

			fadeSpeed = fadeSpeed || XenForo.speed.normal;

			return this
				.xfSlideDown(XenForo.speed.fast)
				.animate({ opacity: 1 }, fadeSpeed, function()
				{
					$(this).ieOpacityFix(callback);
				});
		},

		/**
		 * Animates an element fading out then closing the gap left behind
		 *
		 * @param integer|string Speed of fade-out - if this is zero, there will be no animation at all
		 * @param function Callback function on completion
		 * @param integer|string Slide speed - ignored if fadeSpeed is zero
		 * @param string Easing method
		 *
		 * @return jQuery
		 */
		xfFadeUp: function(fadeSpeed, callback, slideSpeed, easingMethod)
		{
			fadeSpeed = ((typeof fadeSpeed == 'undefined' || fadeSpeed === null) ? XenForo.speed.normal : fadeSpeed);
			slideSpeed = ((typeof slideSpeed == 'undefined' || slideSpeed === null) ? fadeSpeed : slideSpeed);

			return this
				.slideUp({
					duration: Math.max(fadeSpeed, slideSpeed),
					easing: easingMethod || 'swing',
					complete: callback,
					queue: false
				})
				.animate({ opacity: 0, queue: false }, fadeSpeed);
		},

		/**
		 * Inserts and activates content into the DOM, using xfFadeDown to animate the insertion
		 *
		 * @param string jQuery method with which to insert the content
		 * @param string Selector for the previous parameter
		 * @param string jQuery method with which to animate the showing of the content
		 * @param string|integer Speed at which to run the animation
		 * @param function Callback for when the animation is complete
		 *
		 * @return jQuery
		 */
		xfInsert: function(insertMethod, insertReference, animateMethod, animateSpeed, callback)
		{
			if (insertMethod == 'replaceAll')
			{
				$(insertReference).xfFadeUp(animateSpeed);
			}

			this
				.addClass('__XenForoActivator')
				.css('display', 'none')
				[insertMethod || 'appendTo'](insertReference)
				.xfActivate()
				[animateMethod || 'xfFadeDown'](animateSpeed, callback);

			return this;
		},

		/**
		 * Removes an element from the DOM, animating its removal with xfFadeUp
		 * All parameters are optional.
		 *
		 *  @param string animation method
		 *  @param function callback function
		 *  @param integer Sliding speed
		 *  @param string Easing method
		 *
		 * @return jQuery
		 */
		xfRemove: function(animateMethod, callback, slideSpeed, easingMethod)
		{
			return this[animateMethod || 'xfFadeUp'](XenForo.speed.normal, function()
			{
				$(this).empty().remove();

				if ($.isFunction(callback))
				{
					callback();
				}
			}, slideSpeed, easingMethod);
		},

		/**
		 * Prepares an element for xfSlideIn() / xfSlideOut()
		 *
		 * @param boolean If true, return the height of the wrapper
		 *
		 * @return jQuery|integer
		 */
		_xfSlideWrapper: function(getHeight)
		{
			if (!this.data('slidewrapper'))
			{
				this.data('slidewrapper', this.wrap('<div class="_swOuter"><div class="_swInner" /></div>')
					.closest('div._swOuter').css('overflow', 'hidden'));
			}

			if (getHeight)
			{
				try
				{
					return this.data('slidewrapper').height();
				}
				catch (e)
				{
					// so IE11 seems to be randomly throwing an exception in jQuery here, so catch it
					return 0;
				}
			}

			return this.data('slidewrapper');
		},

		/**
		 * Slides content in (down), with content glued to lower edge, drawer-like
		 *
		 * @param duration
		 * @param easing
		 * @param callback
		 *
		 * @return jQuery
		 */
		xfSlideIn: function(duration, easing, callback)
		{
			var $wrap = this._xfSlideWrapper().css('height', 'auto'),
				height = 0;

			$wrap.find('div._swInner').css('margin', 'auto');
			height = this.show(0).outerHeight();

			$wrap
				.css('height', 0)
				.animate({ height: height }, duration, easing, function() {
					$wrap.css('height', '');
				})
			.find('div._swInner')
				.css('marginTop', height * -1)
				.animate({ marginTop: 0 }, duration, easing, callback);

			return this;
		},

		/**
		 * Slides content out (up), reversing xfSlideIn()
		 *
		 * @param duration
		 * @param easing
		 * @param callback
		 *
		 * @return jQuery
		 */
		xfSlideOut: function(duration, easing, callback)
		{
			var height = this.outerHeight();

			this._xfSlideWrapper()
				.animate({ height: 0 }, duration, easing)
			.find('div._swInner')
				.animate({ marginTop: height * -1 }, duration, easing, callback);

			return this;
		},

		/**
		 * Workaround for IE's font-antialiasing bug when dealing with opacity
		 *
		 * @param function Callback
		 */
		ieOpacityFix: function(callback)
		{
			//ClearType Fix
			if (!$.support.opacity)
			{
				this.css('filter', '');
				this.attr('style', this.attr('style').replace(/filter:\s*;/i, ''));
			}

			if ($.isFunction(callback))
			{
				callback.apply(this);
			}

			return this;
		},

		/**
		 * Wraps around jQuery Tools .overlay().
		 *
		 * Prepares overlay options before firing overlay() for best possible experience.
		 * For example, removes fancy (slow) stuff from options for touch browsers.
		 *
		 * @param options
		 *
		 * @returns jQuery
		 */
		overlay: function(options)
		{
			if (XenForo.isTouchBrowser())
			{
				return this._jQueryToolsOverlay($.extend(true, options,
				{
					//mask: false,
					speed: 0,
					loadSpeed: 0
				}));
			}
			else
			{
				return this._jQueryToolsOverlay(options);
			}
		}
	});

	/* jQuery Tools Extensions */

	/**
	 * Effect method for jQuery.tools overlay.
	 * Slides down a container, then fades up the content.
	 * Closes by reversing the animation.
	 */
	$.tools.overlay.addEffect('slideDownContentFade',
		function(position, callback)
		{
			var $overlay = this.getOverlay(),
				conf = this.getConf();

			$overlay.find('.content').css('opacity', 0);

			if (this.getConf().fixed)
			{
				position.position = 'fixed';
			}
			else
			{
				position.position = 'absolute';
				position.top += $(window).scrollTop();
				position.left += $(window).scrollLeft();
			}

			$overlay.css(position).xfSlideDown(XenForo.speed.fast, function()
			{
				$overlay.find('.content').animate({ opacity: 1 }, conf.speed, function() { $(this).ieOpacityFix(callback); });
			});
		},
		function(callback)
		{
			var $overlay = this.getOverlay();

			$overlay.find('.content').animate({ opacity: 0 }, this.getConf().speed, function()
			{
				$overlay.xfSlideUp(XenForo.speed.fast, callback);
			});
		}
	);

	$.tools.overlay.addEffect('slideDown',
		function(position, callback)
		{
			if (this.getConf().fixed)
			{
				position.position = 'fixed';
			}
			else
			{
				position.position = 'absolute';
				position.top += $(window).scrollTop();
				position.left += $(window).scrollLeft();
			}

			this.getOverlay()
				.css(position)
				.xfSlideDown(this.getConf().speed, callback);
		},
		function(callback)
		{
			this.getOverlay().hide(0, callback);
		}
	);

	// *********************************************************************

	$.extend(XenForo,
	{
		/**
		 * Cache for overlays
		 *
		 * @var object
		 */
		_OverlayCache: {},

		/**
		 * Defines whether or not an AJAX request is known to be in progress
		 *
		 *  @var boolean
		 */
		_AjaxProgress: false,

		/**
		 * Defines a variable that can be overridden to force/control the base HREF
		 * used to canonicalize AJAX requests
		 *
		 * @var string
		 */
		ajaxBaseHref: '',

		/**
		 * Counter for unique ID generation
		 *
		 * @var integer
		 */
		_uniqueIdCounter: 0,

		/**
		 * Configuration for overlays, should be redefined in the PAGE_CONTAINER template HTML
		 *
		 * @var object
		 */
		_overlayConfig: {},

		/**
		 * Contains the URLs of all externally loaded resources from scriptLoader
		 *
		 * @var object
		 */
		_loadedScripts: {},

		/**
		 * Configuration for cookies
		 *
		 * @var object
		 */
		_cookieConfig: { path: '/', domain: '', 'prefix': 'xf_'},

		/**
		 * Flag showing whether or not the browser window has focus. On load, assume true.
		 *
		 * @var boolean
		 */
		_hasFocus: true,

		/**
		 * @var object List of server-related time info (now, today, todayDow)
		 */
		serverTimeInfo: {},

		/**
		 * @var object Information about the XenForo visitor. Usually contains user_id.
		 */
		visitor: {},

		/**
		 * @var integer Time the page was loaded.
		 */
		_pageLoadTime: (new Date()).getTime() / 1000,

		/**
		 * JS version key, to force refreshes when needed
		 *
		 * @var string
		 */
		_jsVersion: '',

		/**
		 * If true, disables reverse tabnabbing protection
		 *
		 * @var bool
		 */
		_noRtnProtect: false,

		/**
		 * CSRF Token
		 *
		 * @var string
		 */
		_csrfToken: '',

		/**
		 * URL to CSRF token refresh.
		 *
		 * @var string
		 */
		_csrfRefreshUrl: '',

		_noSocialLogin: false,

		/**
		 * Speeds for animation
		 *
		 * @var object
		 */
		speed:
		{
			xxfast: 50,
			xfast: 100,
			fast: 200,
			normal: 400,
			slow: 600
		},

		/**
		 * Multiplier for animation speeds
		 *
		 * @var float
		 */
		_animationSpeedMultiplier: 1,

		/**
		 * Enable overlays or use regular pages
		 *
		 * @var boolean
		 */
		_enableOverlays: true,

		/**
		 * Enables AJAX submission via AutoValidator. Doesn't change things other than
		 * that. Useful to disable for debugging.
		 *
		 * @var boolean
		 */
		_enableAjaxSubmit: true,

		/**
		 * Determines whether the lightbox shows all images from the current page,
		 * or just from an individual message
		 *
		 * @var boolean
		 */
		_lightBoxUniversal: false,

		/**
		 * @var object Phrases
		 */
		phrases: {},

		/**
		 * Binds all registered functions to elements within the DOM
		 */
		init: function()
		{
			var dStart = new Date(),
				xfFocus = function()
				{
					XenForo._hasFocus = true;
					$(document).triggerHandler('XenForoWindowFocus');
				},
				xfBlur = function()
				{
					XenForo._hasFocus = false;
					$(document).triggerHandler('XenForoWindowBlur');
				},
				$html = $('html');

			if ($.browser.msie)
			{
				$(document).bind(
				{
					focusin:  xfFocus,
					focusout: xfBlur
				});
			}
			else
			{
				$(window).bind(
				{
					focus: xfFocus,
					blur:  xfBlur
				});
			}

			$(window).on('resize', function() {
				XenForo.checkQuoteSizing($(document));
			});

			// Set the animation speed based around the style property speed multiplier
			XenForo.setAnimationSpeed(XenForo._animationSpeedMultiplier);

			// Periodical timestamp refresh
			XenForo._TimestampRefresh = new XenForo.TimestampRefresh();

			// Find any ignored content that has not been picked up by PHP
			XenForo.prepareIgnoredContent();

			// init ajax progress indicators
			XenForo.AjaxProgress();

			// Activate all registered controls
			XenForo.activate(document);

			$(document).on('click', '.bbCodeQuote .quoteContainer .quoteExpand', function(e) {
				$(this).closest('.quoteContainer').toggleClass('expanded');
			});

			XenForo.watchProxyLinks();
			if (!XenForo._noRtnProtect)
			{
				XenForo.watchExternalLinks();
			}

			// make the breadcrumb and navigation responsive
			if (!$html.hasClass('NoResponsive'))
			{
				XenForo.updateVisibleBreadcrumbs();
				XenForo.updateVisibleNavigationTabs();
				XenForo.updateVisibleNavigationLinks();

				var resizeTimer, htmlWidth = $html.width();
				$(window).on('resize orientationchange load', function(e) {
					if (resizeTimer)
					{
						return;
					}
					if (e.type != 'load' && $html.width() == htmlWidth)
					{
						return;
					}
					htmlWidth = $html.width();
					resizeTimer = setTimeout(function() {
						resizeTimer = 0;
						XenForo.updateVisibleBreadcrumbs();
						XenForo.updateVisibleNavigationTabs();
						XenForo.updateVisibleNavigationLinks();
					}, 20);
				});
				$(document).on('click', '.breadcrumb .placeholder', function() {
					$(this).closest('.breadcrumb').addClass('showAll');
					XenForo.updateVisibleBreadcrumbs();
				});
			}

			// Periodical CSRF token refresh
			XenForo._CsrfRefresh = new XenForo.CsrfRefresh();

			// Autofocus for non-supporting browsers
			if (!('autofocus' in document.createElement('input')))
			{
				//TODO: work out a way to prevent focusing if something else already has focus http://www.w3.org/TR/html5/forms.html#attr-fe-autofocus
				$('input[autofocus], textarea[autofocus], select[autofocus]').first().focus();
			}


			// init Tweet buttons
			XenForo.tweetButtonInit();

			console.info('XenForo.init() %dms. jQuery %s/%s', new Date() - dStart, $().jquery, $.tools.version);

			if ($('#ManualDeferredTrigger').length)
			{
				setTimeout(XenForo.manualDeferredHandler, 100);
			}

			if ($('html.RunDeferred').length)
			{
				setTimeout(XenForo.runAutoDeferred, 100);
			}
		},

		runAutoDeferred: function() {
			XenForo.ajax('deferred.php', {}, function(ajaxData) {
				if (ajaxData && ajaxData.moreDeferred)
				{
					setTimeout(XenForo.runAutoDeferred, 100);
				}
			}, { error: false, global: false });
		},

		prepareIgnoredContent: function()
		{
			var $displayLink = $('a.DisplayIgnoredContent'),
				namesObj = {},
				namesArr = [];

			if ($displayLink.length)
			{
				$('.ignored').each(function()
				{
					var name = $(this).data('author');
					if (name)
					{
						namesObj[name] = true;
					}
				});

				$.each(namesObj, function(name)
				{
					namesArr.push(name);
				});

				if (namesArr.length)
				{
					$displayLink.attr('title', XenForo.phrases['show_hidden_content_by_x'].replace(/\{names\}/, namesArr.join(', ')));
					$displayLink.parent().show();
				}
			}
		},

		watchProxyLinks: function()
		{
			var proxyLinkClick = function(e)
			{
				var $this = $(this),
					proxyHref = $this.data('proxy-href'),
					lastEvent = $this.data('proxy-handler-last');

				if (!proxyHref)
				{
					return;
				}

				// we may have a direct click event and a bubbled event. Ensure they don't both fire.
				if (lastEvent && lastEvent == e.timeStamp)
				{
					return;
				}
				$this.data('proxy-handler-last', e.timeStamp);

				XenForo.ajax(proxyHref, {}, function(ajaxData) {}, { error: false, global: false });
			};

			$(document)
				.on('click', 'a.ProxyLink', proxyLinkClick)
				.on('focusin', 'a.ProxyLink', function(e)
				{
					// This approach is taken because middle click events do not bubble. This is a way of
					// getting the equivalent of event bubbling on middle clicks in Chrome.
					var $this = $(this);
					if ($this.data('proxy-handler'))
					{
						return;
					}

					$this.data('proxy-handler', true)
						.click(proxyLinkClick);
				});
		},

		watchExternalLinks: function()
		{
			var externalLinkClick = function(e)
			{
				if (e.isDefaultPrevented())
				{
					return;
				}

				var $this = $(this),
					href = $this.attr('href'),
					lastEvent = $this.data('blank-handler-last');
				if (!href)
				{
					return;
				}

				if (href.match(/^[a-z]:/i) && !href.match(/^https?:/i))
				{
					// ignore canonical but non http(s) links
					return;
				}

				href = XenForo.canonicalizeUrl(href);

				var regex = new RegExp('^[a-z]+://' + location.host + '(/|$|:)', 'i');
				if (regex.test(href) && !$this.hasClass('ProxyLink'))
				{
					// if the link is local, then don't do the special processing... unless it's a proxy link
					// so it's likely to be external after the redirect
					return;
				}

				// we may have a direct click event and a bubbled event. Ensure they don't both fire.
				if (lastEvent && lastEvent == e.timeStamp)
				{
					return;
				}

				$this.data('blank-handler-last', e.timeStamp);

				var ua = navigator.userAgent,
					isOldIE = ua.indexOf('MSIE') !== -1,
					isSafari = ua.indexOf('Safari') !== -1 && ua.indexOf('Chrome') == -1,
					isGecko = ua.indexOf('Gecko/') !== -1;

				if (e.shiftKey && isGecko)
				{
					// Firefox doesn't trigger when holding shift. If the code below runs, it will force
					// opening in a new tab instead of a new window, so stop. Note that Chrome still triggers here,
					// but it does open in a new window anyway so we run the normal code.
					return;
				}
				if (isSafari && (e.shiftKey || e.altKey))
				{
					// this adds to reading list or downloads instead of opening a new tab
					return;
				}
				if (isOldIE)
				{
					// IE has mitigations for this and this blocks referrers
					return;
				}

				// now run the opener clearing

				if (isSafari)
				{
					// Safari doesn't work with the other approach
					// Concept from: https://github.com/danielstjules/blankshield
					var $iframe, iframeDoc, $script;

					$iframe = $('<iframe style="display: none" />').appendTo(document.body);
					iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;

					iframeDoc.__href = href; // set this so we don't need to do an eval-type thing

					$script = $('<script />', iframeDoc);
					$script[0].text = 'window.opener=null;' +
						'window.parent=null;window.top=null;window.frameElement=null;' +
						'window.open(document.__href).opener = null;';

					iframeDoc.body.appendChild($script[0]);
					$iframe.remove();
				}
				else
				{
					// use this approach for the rest to maintain referrers when possible
					var w = window.open(href);

					try
					{
						// this can potentially fail, don't want to break
						w.opener = null;
					}
					catch (e) {}
				}

				e.preventDefault();
			};

			$(document)
				.on('click', 'a[target=_blank]', externalLinkClick)
				.on('focusin', 'a[target=_blank]', function(e)
				{
					// This approach is taken because middle click events do not bubble. This is a way of
					// getting the equivalent of event bubbling on middle clicks in Chrome.
					var $this = $(this);
					if ($this.data('blank-handler'))
					{
						return;
					}

					$this.data('blank-handler', true)
						.click(externalLinkClick);
				});
		},

		/**
		 * Asynchronously load the specified JavaScript, with an optional callback on completion.
		 *
		 * @param string Script source
		 * @param object Callback function
		 * @param string innerHtml for the script tags
		 */
		loadJs: function(src, callback, innerHTML)
		{
			try
			{
				var script = document.createElement('script');
				script.async = true;
				if (innerHTML)
				{
					try
					{
						script.innerHTML = innerHTML;
					}
					catch(e2) {}
				}
				var f = function()
				{
					if (callback)
					{
						callback();
						callback = null;
					}
				};
				script.onload = f;
				script.onreadystatechange = function()
				{
					if (script.readyState === 'loaded')
					{
						f();
					}
				};
				script.src = src;
				document.getElementsByTagName('head')[0].appendChild(script);
			}
			catch(e) {}
		},

		/**
		 * Asynchronously load the Twitter button JavaScript.
		 */
		tweetButtonInit: function()
		{
			if ($('a.twitter-share-button').length)
			{
				XenForo.loadJs('https://platform.twitter.com/widgets.js');
			}
		},

		/**
		 * Asynchronously load the +1 button JavaScript.
		 */
		plusoneButtonInit: function(el)
		{
			if ($(el).find('div.g-plusone, .GoogleLogin').length)
			{
				var locale = $('html').attr('lang');

				var callback = function()
				{
					if (!window.gapi)
					{
						return;
					}

					$(el).find('.GoogleLogin').each(function() {
						var $button = $(this),
							clientId = $button.data('client-id');

						gapi.signin.render(this, {
							callback: function(result) {
								if (result.status.method == 'AUTO')
								{
									// this is an auto triggered login which is doesn't really fit
									// and can cause some bad behavior, so disable it
									return;
								}
								if (result.code)
								{
									window.location = XenForo.canonicalizeUrl(
										$button.data('redirect-url').replace('__CODE__', result.code)
									);
								}
							},
							clientid: clientId,
							cookiepolicy: 'single_host_origin',
							accesstype: 'offline',
							immediate: false,
							requestvisibleactions: 'http://schemas.google.com/AddActivity',
							scope: 'https://www.googleapis.com/auth/plus.login email'
						});
					});
				};

				if (window.___gcfg && window.gapi)
				{
					callback();
				}
				else
				{
					window.___gcfg = {
						lang: locale,
						isSignedOut: true // this is to stop the "welcome back" prompt as it doesn't fit with our flow
					};

					XenForo.loadJs('https://plus.google.com/js/client:plusone.js', callback);
				}
			}
		},

		/**
		 * Prevents Google Chrome's AutoFill from turning inputs yellow.
		 * Adapted from http://www.benjaminmiles.com/2010/11/22/fixing-google-chromes-yellow-autocomplete-styles-with-jquery/
		 */
		chromeAutoFillFix: function($root)
		{
			if ($.browser.webkit && navigator.userAgent.toLowerCase().indexOf('chrome') >= 0)
			{
				if (!$root)
				{
					$root = $(document);
				}

				// trap an error here - CloudFlare RocketLoader causes an error with this.
				var $inputs;
				try
				{
					$inputs = $root.find('input:-webkit-autofill');
				}
				catch (e)
				{
					$inputs = $([]);
				}

				if ($inputs.length)
				{
					$inputs.each(function(i)
					{
						var $this = $(this),
							val = $this.val();

						if (!val || !val.length)
						{
							return;
						}

						$this.after($this.clone(true).val(val)).remove();
					});
				}
			}
		},

		updateVisibleBreadcrumbs: function()
		{
			$('.breadcrumb').each(function() {
				var container = this,
					$container = $(container);

				$container.find('.placeholder').remove();

				var $crusts = $container.find('.crust');
				$crusts.removeClass('firstVisibleCrumb').show();

				var $homeCrumb = $crusts.filter('.homeCrumb');

				$container.css('height', '');
				var beforeHeight = container.offsetHeight;
				$container.css('height', 'auto');

				if (container.offsetHeight <= beforeHeight)
				{
					$container.css('height', '');
					return;
				}

				var $lastHidden = null,
					hideSkipSelector = '.selectedTabCrumb, :last-child';

				$crusts.each(function() {
					var $crust = $(this);
					if ($crust.is(hideSkipSelector))
					{
						return true;
					}

					$crust.hide();
					$lastHidden = $crust;
					return (container.offsetHeight > beforeHeight);
				});

				if (!$lastHidden)
				{
					$container.css('height', '');
					return;
				}

				var $placeholder = $('<span class="crust placeholder"><a class="crumb" href="javascript:"><span>...</span></a><span class="arrow"><span>&gt;</span></span></span>');
				$lastHidden.after($placeholder);

				if (container.offsetHeight > beforeHeight)
				{
					var $prev = $lastHidden.prevAll('.crust:not(' + hideSkipSelector + ')').last();
					if ($prev.length)
					{
						$prev.hide();
					}
				}

				if (container.offsetHeight > beforeHeight)
				{
					var $next = $lastHidden.nextAll('.crust:not(.placeholder, ' + hideSkipSelector + ')').first();
					if ($next.length)
					{
						$next.hide();
						$next.after($placeholder);
					}
				}

				if ($homeCrumb.length && !$homeCrumb.is(':visible'))
				{
					$container.find('.crust:visible:first').addClass('firstVisibleCrumb');
				}

				if (container.offsetHeight <= beforeHeight)
				{
					// firefox doesn't seem to contain the breadcrumbs despite the overflow hidden
					$container.css('height', '');
				}
			});
		},

		updateVisibleNavigationTabs: function()
		{
			var $tabs = $('#navigation').find('.navTabs');
			if (!$tabs.length)
			{
				return;
			}

			var	tabsCoords = $tabs.coords(),
				$publicTabs = $tabs.find('.publicTabs'),
				$publicInnerTabs = $publicTabs.find('> .navTab'),
				$visitorTabs = $tabs.find('.visitorTabs'),
				$visitorInnerTabs = $visitorTabs.find('> .navTab'),
				$visitorCounter = $('#VisitorExtraMenu_Counter'),
				maxPublicWidth,
				$hiddenTab = $publicInnerTabs.filter('.navigationHiddenTabs');

			$publicInnerTabs.show();
			$hiddenTab.hide();

			$visitorInnerTabs.show();
			$visitorCounter.addClass('ResponsiveOnly');

			if ($tabs.is('.showAll'))
			{
				return;
			}

			maxPublicWidth = $tabs.width() - $visitorTabs.width() - 1;

			var hidePublicTabs = function()
				{
					var shownSel = '.selected, .navigationHiddenTabs';

					var $hideable = $publicInnerTabs.filter(':not(' + shownSel + ')'),
						$hiddenList = $('<ul />'),
						hiddenCount = 0,
						overflowMenuShown = false;

					$.each($hideable.get().reverse(), function()
					{
						var $this = $(this);
						if (isOverflowing($publicTabs.coords(), true))
						{
							$hiddenList.prepend(
								$('<li />').html($this.find('.navLink').clone())
							);
							$this.hide();
							hiddenCount++;
						}
						else
						{
							if (hiddenCount)
							{
								$hiddenTab.show();

								if (isOverflowing($publicTabs.coords(), true))
								{
									$hiddenList.prepend(
										$('<li />').html($this.find('.navLink').clone())
									);
									$this.hide();
									hiddenCount++;
								}
								$('#NavigationHiddenMenu').html($hiddenList).xfActivate();
								overflowMenuShown = true;
							}
							else
							{
								$hiddenTab.hide();
							}

							return false;
						}
					});

					if (hiddenCount && !overflowMenuShown)
					{
						$hiddenTab.show();
						$('#NavigationHiddenMenu').html($hiddenList).xfActivate();
					}
				},
				hideVisitorTabs = function() {
					$visitorInnerTabs.hide();
					$visitorInnerTabs.filter('.account, .selected').show();
					$visitorCounter.removeClass('ResponsiveOnly');
				},
				isOverflowing = function(coords, checkMax) {
					if (
						coords.top >= tabsCoords.top + tabsCoords.height
						|| coords.height >= tabsCoords.height * 2
					)
					{
						return true;
					}

					if (checkMax && coords.width > maxPublicWidth)
					{
						return true;
					}

					return false;
				};

			if ($visitorTabs.length)
			{
				if (isOverflowing($visitorTabs.coords()))
				{
					hidePublicTabs();

					if (isOverflowing($visitorTabs.coords()))
					{
						hideVisitorTabs();
					}
				}
			}
			else if (isOverflowing($publicTabs.coords()))
			{
				hidePublicTabs();
			}
		},

		updateVisibleNavigationLinks: function()
		{
			var $linksList = $('#navigation').find('.navTab.selected .blockLinksList');
			if (!$linksList.length)
			{
				return;
			}

			var	$links = $linksList.find('> li'),
				listOffset = $linksList.offset(),
				$hidden = $links.filter('.navigationHidden'),
				$firstHidden = false;

			$links.show();
			$hidden.hide();

			if ($linksList.is('.showAll'))
			{
				return;
			}

			var hiddenForMenu = [],
				$lastLink = $links.filter(':not(.navigationHidden)').last(),
				hideOffset = 0,
				hasHidden = false,
				lastCoords,
				$link;

			if (!$lastLink.length)
			{
				return;
			}

			do
			{
				lastCoords = $lastLink.coords();
				if (lastCoords.top > listOffset.top + lastCoords.height)
				{
					$link = $links.eq(hideOffset);
					$link.hide();
					hiddenForMenu.push($link);
					hideOffset++;

					if (!hasHidden)
					{
						hasHidden = true;

						if (!$hidden.length)
						{
							$hidden = $('<li class="navigationHidden Popup PopupControl PopupClosed"><a rel="Menu" class="NoPopupGadget">...</a><div class="Menu blockLinksList primaryContent" id="NavigationLinksHiddenMenu"></div></li>');
							$linksList.append($hidden);
							new XenForo.PopupMenu($hidden);
						}
						else
						{
							$hidden.show();
						}
					}
				}
				else
				{
					break;
				}
			}
			while (hideOffset < $links.length);

			if (hasHidden)
			{
				if (hideOffset < $links.length)
				{
					var coords = $hidden.coords();
					if (coords.top > listOffset.top + coords.height)
					{
						$link = $links.eq(hideOffset);
						$link.hide();
						hiddenForMenu.push($link);
					}
				}

				var $hiddenList = $('<ul />');
				$(hiddenForMenu).each(function() {
					$hiddenList.append(
						$('<li />').html($(this).find('a').clone())
					);
				});
				$('#NavigationLinksHiddenMenu').html($hiddenList).xfActivate();
			}
		},

		/**
		 * Binds a function to elements to fire on a custom event
		 *
		 * @param string jQuery selector - to get the elements to be bound
		 * @param function Function to fire
		 * @param string Custom event name (if empty, assume 'XenForoActivateHtml')
		 */
		register: function(selector, fn, event)
		{
			if (typeof fn == 'string')
			{
				var className = fn;
				fn = function(i)
				{
					XenForo.create(className, this);
				};
			}

			$(document).bind(event || 'XenForoActivateHtml', function(e)
			{
				$(e.element).find(selector).each(fn);
			});
		},

		/**
		 * Creates a new object of class XenForo.{functionName} using
		 * the specified element, unless one has already been created.
		 *
		 * @param string Function name (property of XenForo)
		 * @param object HTML element
		 *
		 * @return object XenForo[functionName]($(element))
		 */
		create: function(className, element)
		{
			var $element = $(element),
				xfObj = window,
				parts = className.split('.'), i;

			for (i = 0; i < parts.length; i++) { xfObj = xfObj[parts[i]]; }

			if (typeof xfObj != 'function')
			{
				return console.error('%s is not a function.', className);
			}

			if (!$element.data(className))
			{
				$element.data(className, new xfObj($element));
			}

			return $element.data(className);
		},

		/**
		 * Fire the initialization events and activate functions for the specified element
		 *
		 * @param object Usually jQuery
		 *
		 * @return object
		 */
		activate: function(element)
		{
			var $element = $(element);

			console.group('XenForo.activate(%o)', element);

			$element.trigger('XenForoActivate').removeClass('__XenForoActivator');
			$element.find('noscript').empty().remove();

			XenForo._TimestampRefresh.refresh(element, true);

			$(document)
				.trigger({ element: element, type: 'XenForoActivateHtml' })
				.trigger({ element: element, type: 'XenForoActivatePopups' })
				.trigger({ element: element, type: 'XenForoActivationComplete' });

			var $form = $element.find('form.AutoSubmit:first');
			if ($form.length)
			{
				$(document).trigger('PseudoAjaxStart');
				$form.submit();
				$form.find('input[type="submit"], input[type="reset"]').hide();
			}

			XenForo.checkQuoteSizing($element);
			XenForo.plusoneButtonInit(element);
			XenForo.Facebook.start();

			console.groupEnd();

			return element;
		},

		checkQuoteSizing: function($element)
		{
			$element.find('.bbCodeQuote .quoteContainer').each(function() {
				var self = this,
					delay = 0,
					checkHeight = function() {
						var $self = $(self),
							quote = $self.find('.quote')[0];

						if (!quote)
						{
							return;
						}

						if (quote.scrollHeight == 0 || quote.offsetHeight == 0)
						{
							if (delay < 2000)
							{
								setTimeout(checkHeight, delay);
								delay += 100;
							}
							return;
						}

						// +1 resolves a chrome rounding issue
						if (quote.scrollHeight > quote.offsetHeight + 1)
						{
							$self.find('.quoteExpand').addClass('quoteCut');
						}
						else
						{
							$self.find('.quoteExpand').removeClass('quoteCut');
						}
					};

				checkHeight();
				$(this).find('img').one('load', checkHeight);
				$(this).on('elementResized', checkHeight);
			});
		},

		/**
		 * Pushes an additional parameter onto the data to be submitted via AJAX
		 *
		 * @param array|string Data parameters - either from .serializeArray() or .serialize()
		 * @param string Name of parameter
		 * @param mixed Value of parameter
		 *
		 * @return array|string Data including new parameter
		 */
		ajaxDataPush: function(data, name, value)
		{
			if (!data || typeof data == 'string')
			{
				// data is empty, or a url string - &name=value
				data = String(data);
				data += '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
			}
			else if (data[0] !== undefined)
			{
				// data is a numerically-keyed array of name/value pairs
				data.push({ name: name, value: value });
			}
			else
			{
				// data is an object with a single set of name & value properties
				data[name] = value;
			}

			return data;
		},

		/**
		 * Wraps around jQuery's own $.ajax function, with our own defaults provided.
		 * Will submit via POST and expect JSON back by default.
		 * Server errors will be handled using XenForo.handleServerError
		 *
		 * @param string URL to load
		 * @param object Data to pass
		 * @param function Success callback function
		 * @param object Additional options to override or extend defaults
		 *
		 * @return XMLHttpRequest
		 */
		ajax: function(url, data, success, options)
		{
			if (!url)
			{
				return console.error('No URL specified for XenForo.ajax()');
			}

			url = XenForo.canonicalizeUrl(url, XenForo.ajaxBaseHref);

			data = XenForo.ajaxDataPush(data, '_xfRequestUri', window.location.pathname + window.location.search);
			data = XenForo.ajaxDataPush(data, '_xfNoRedirect', 1);
			if (XenForo._csrfToken)
			{
				data = XenForo.ajaxDataPush(data, '_xfToken', XenForo._csrfToken);
			}

			var successCallback = function(ajaxData, textStatus)
			{
				if (typeof ajaxData == 'object')
				{
					if (typeof ajaxData._visitor_conversationsUnread != 'undefined')
					{
						XenForo.balloonCounterUpdate($('#ConversationsMenu_Counter'), ajaxData._visitor_conversationsUnread);
						XenForo.balloonCounterUpdate($('#AlertsMenu_Counter'), ajaxData._visitor_alertsUnread);
						XenForo.balloonCounterUpdate($('#VisitorExtraMenu_ConversationsCounter'), ajaxData._visitor_conversationsUnread);
						XenForo.balloonCounterUpdate($('#VisitorExtraMenu_AlertsCounter'), ajaxData._visitor_alertsUnread);
						XenForo.balloonCounterUpdate($('#VisitorExtraMenu_Counter'),
							(
								parseInt(ajaxData._visitor_conversationsUnread, 10) + parseInt(ajaxData._visitor_alertsUnread, 10)
								|| 0
							).toString()
						);
					}

					if (ajaxData._manualDeferred)
					{
						XenForo.manualDeferredHandler();
					}
					else if (ajaxData._autoDeferred)
					{
						XenForo.runAutoDeferred();
					}
				}

				$(document).trigger(
				{
					type: 'XFAjaxSuccess',
					ajaxData: ajaxData,
					textStatus: textStatus
				});

				success.call(null, ajaxData, textStatus);
			};

			var referrer = window.location.href;
			if (referrer.match(/[^\x20-\x7f]/))
			{
				var a = document.createElement('a');
				a.href = '';
				referrer = referrer.replace(a.href, XenForo.baseUrl());
			}

			options = $.extend(true,
			{
				data: data,
				url: url,
				success: successCallback,
				type: 'POST',
				dataType: 'json',
				error: function(xhr, textStatus, errorThrown)
				{
					if (xhr.readyState == 0)
					{
						return;
					}

					try
					{
						// attempt to pass off to success, if we can decode JSON from the response
						successCallback.call(null, $.parseJSON(xhr.responseText), textStatus);
					}
					catch (e)
					{
						// not valid JSON, trigger server error handler
						XenForo.handleServerError(xhr, textStatus, errorThrown);
					}
				},
				headers: {'X-Ajax-Referer': referrer},
				timeout: 30000 // 30s
			}, options);

			// override standard extension, depending on dataType
			if (!options.data._xfResponseType)
			{
				switch (options.dataType)
				{
					case 'html':
					case 'json':
					case 'xml':
					{
						// pass _xfResponseType parameter to override default extension
						options.data = XenForo.ajaxDataPush(options.data, '_xfResponseType', options.dataType);
						break;
					}
				}
			}

			return $.ajax(options);
		},

		/**
		 * Updates the total in one of the navigation balloons, showing or hiding if necessary
		 *
		 * @param jQuery $balloon
		 * @param string counter
		 */
		balloonCounterUpdate: function($balloon, newTotal)
		{
			if ($balloon.length)
			{
				var $counter = $balloon.find('span.Total'),
					oldTotal = $counter.text();

				$counter.text(newTotal);

				if (!newTotal || newTotal == '0')
				{
					$balloon.fadeOut('fast', function() {
						$balloon.addClass('Zero').css('display', '');
					});
				}
				else
				{
					$balloon.fadeIn('fast', function()
					{
						$balloon.removeClass('Zero').css('display', '');

						var oldTotalInt = parseInt(oldTotal.replace(/[^\d]/, ''), 10),
							newTotalInt = parseInt(newTotal.replace(/[^\d]/, ''), 10),
							newDifference = newTotalInt - oldTotalInt;

						if (newDifference > 0 && $balloon.data('text'))
						{
							var $container = $balloon.closest('.Popup'),
								PopupMenu = $container.data('XenForo.PopupMenu'),
								$message;

							$message = $('<a />').css('cursor', 'pointer').html($balloon.data('text').replace(/%d/, newDifference)).click(function(e)
							{
								if ($container.is(':visible') && PopupMenu)
								{
									PopupMenu.$clicker.trigger('click');
								}
								else if ($container.find('a[href]').length)
								{
									window.location = XenForo.canonicalizeUrl($container.find('a[href]').attr('href'));
								}
								return false;
							});

							if (PopupMenu && !PopupMenu.menuVisible)
							{
								PopupMenu.resetLoader();
							}

							XenForo.stackAlert($message, 10000, $balloon);
						}
					});
				}
			}
		},

		_manualDeferUrl: '',
		_manualDeferOverlay: false,
		_manualDeferXhr: false,

		manualDeferredHandler: function()
		{
			if (!XenForo._manualDeferUrl || XenForo._manualDeferOverlay)
			{
				return;
			}

			var processing = XenForo.phrases['processing'] || 'Processing',
				cancel = XenForo.phrases['cancel'] || 'Cancel',
				cancelling = XenForo.phrases['cancelling'] || 'Cancelling';

			var $html = $('<div id="ManualDeferOverlay" class="xenOverlay"><h2 class="titleBar">'
					+ processing + '... '
					+ '<a class="CancelDeferred button" data-cancelling="' + cancelling + '..." style="display:none">' + cancel + '</a></h2>'
					+ '<span class="processingText">' + processing + '...</span><span class="close"></span></div>');

			$html.find('.CancelDeferred').click(function(e) {
				e.preventDefault();
				$.setCookie('cancel_defer', '1');
				$(this).text($(this).data('cancelling'));
			});

			$html.appendTo('body').overlay($.extend(true, {
				mask: {
					color: 'white',
					opacity: 0.6,
					loadSpeed: XenForo.speed.normal,
					closeSpeed: XenForo.speed.fast
				},
				closeOnClick: false,
				closeOnEsc: false,
				oneInstance: false
			}, XenForo._overlayConfig, {top: '20%'}));
			$html.overlay().load();

			XenForo._manualDeferOverlay = $html;

			$(document).trigger('PseudoAjaxStart');

			var closeOverlay = function()
			{
				XenForo._manualDeferOverlay.overlay().close();
				$('#ManualDeferOverlay').remove();
				XenForo._manualDeferOverlay = false;
				XenForo._manualDeferXhr = false;

				$(document).trigger('PseudoAjaxStop');
				$(document).trigger('ManualDeferComplete');
			};

			var fn = function() {
				XenForo._manualDeferXhr = XenForo.ajax(XenForo._manualDeferUrl, {execute: 1}, function(ajaxData) {
					if (ajaxData && ajaxData.continueProcessing)
					{
						setTimeout(fn, 0);
						XenForo._manualDeferOverlay.find('span').text(ajaxData.status);

						var $cancel = XenForo._manualDeferOverlay.find('.CancelDeferred');
						if (ajaxData.canCancel)
						{
							$cancel.show();
						}
						else
						{
							$cancel.hide();
						}
					}
					else
					{
						closeOverlay();
					}
				}).fail(closeOverlay);
			};
			fn();
		},

		/**
		 * Generic handler for server-level errors received from XenForo.ajax
		 * Attempts to provide a useful error message.
		 *
		 * @param object XMLHttpRequest
		 * @param string Response text
		 * @param string Error thrown
		 *
		 * @return boolean False
		 */
		handleServerError: function(xhr, responseText, errorThrown)
		{
			// handle timeout and parse error before attempting to decode an error
			switch (responseText)
			{
				case 'abort':
				{
					return false;
				}
				case 'timeout':
				{
					XenForo.alert(
						XenForo.phrases.server_did_not_respond_in_time_try_again,
						XenForo.phrases.following_error_occurred + ':'
					);
					return false;
				}
				case 'parsererror':
				{
					console.error('PHP ' + xhr.responseText);
					XenForo.alert('The server responded with an error. The error message is in the JavaScript console.');
					return false;
				}
				case 'notmodified':
				case 'error':
				{
					if (!xhr || !xhr.responseText)
					{
						// this is likely a user cancellation, so just return
						return false;
					}
					break;
				}
			}

			var contentTypeHeader = xhr.getResponseHeader('Content-Type'),
				contentType = false,
				data;

			if (contentTypeHeader)
			{
				switch (contentTypeHeader.split(';')[0])
				{
					case 'application/json':
					{
						contentType = 'json';
						break;
					}
					case 'text/html':
					{
						contentType = 'html';
						break;
					}
					default:
					{
						if (xhr.responseText.substr(0, 1) == '{')
						{
							contentType = 'json';
						}
						else if (xhr.responseText.substr(0, 9) == '<!DOCTYPE')
						{
							contentType = 'html';
						}
					}
				}
			}

			if (contentType == 'json' && xhr.responseText.substr(0, 1) == '{')
			{
				// XMLHttpRequest response is probably JSON
				try
				{
					data = $.parseJSON(xhr.responseText);
				}
				catch (e) {}

				if (data)
				{
					XenForo.hasResponseError(data, xhr.status);
				}
				else
				{
					XenForo.alert(xhr.responseText, XenForo.phrases.following_error_occurred + ':');
				}
			}
			else
			{
				// XMLHttpRequest is some other type...
				XenForo.alert(xhr.responseText, XenForo.phrases.following_error_occurred + ':');
			}

			return false;
		},

		/**
		 * Checks for the presence of an 'error' key in the provided data
		 * and displays its contents if found, using an alert.
		 *
		 * @param object ajaxData
		 * @param integer HTTP error code (optional)
		 *
		 * @return boolean|string Returns the error string if found, or false if not found.
		 */
		hasResponseError: function(ajaxData, httpErrorCode)
		{
			if (typeof ajaxData != 'object')
			{
				XenForo.alert('Response not JSON!'); // debug info, no phrasing
				return true;
			}

			if (ajaxData.errorTemplateHtml)
			{
				new XenForo.ExtLoader(ajaxData, function(data) {
					var $overlayHtml = XenForo.alert(
						ajaxData.errorTemplateHtml,
						XenForo.phrases.following_error_occurred + ':'
					);
					if ($overlayHtml)
					{
						$overlayHtml.find('div.errorDetails').removeClass('baseHtml');
						if (ajaxData.errorOverlayType)
						{
							$overlayHtml.closest('.errorOverlay').removeClass('errorOverlay').addClass(ajaxData.errorOverlayType);
						}
					}
				});

				return ajaxData.error || true;
			}
			else if (ajaxData.error !== undefined)
			{
				// TODO: ideally, handle an array of errors
				if (typeof ajaxData.error === 'object')
				{
					var key;
					for (key in ajaxData.error)
					{
						break;
					}
					ajaxData.error = ajaxData.error[key];
				}

				XenForo.alert(
					ajaxData.error + '\n'
						+ (ajaxData.traceHtml !== undefined ? '<ol class="traceHtml">\n' + ajaxData.traceHtml + '</ol>' : ''),
					XenForo.phrases.following_error_occurred + ':'
				);

				return ajaxData.error;
			}
			else if (ajaxData.status == 'ok' && ajaxData.message)
			{
				XenForo.alert(ajaxData.message, '', 4000);
				return true;
			}
			else
			{
				return false;
			}
		},

		/**
		 * Checks that the supplied ajaxData has a key that can be used to create a jQuery object
		 *
		 *  @param object ajaxData
		 *  @param string key to look for (defaults to 'templateHtml')
		 *
		 *  @return boolean
		 */
		hasTemplateHtml: function(ajaxData, templateKey)
		{
			templateKey = templateKey || 'templateHtml';

			if (!ajaxData[templateKey])
			{
				return false;
			}
			if (typeof(ajaxData[templateKey].search) == 'function')
			{
				return (ajaxData[templateKey].search(/\S+/) !== -1);
			}
			else
			{
				return true;
			}
		},

		/**
		 * Creates an overlay using the given HTML
		 *
		 * @param jQuery Trigger element
		 * @param string|jQuery HTML
		 * @param object Extra options for overlay, will override defaults if specified
		 *
		 * @return jQuery Overlay API
		 */
		createOverlay: function($trigger, templateHtml, extraOptions)
		{
			var $overlay = null,
				$templateHtml = null,
				api = null,
				overlayOptions = null,
				regex = /<script[^>]*>([\s\S]*?)<\/script>/ig,
				regexMatch,
				scripts = [],
				i;

			if (templateHtml instanceof jQuery && templateHtml.is('.xenOverlay'))
			{
				// this is an object that has already been initialised
				$overlay = templateHtml.appendTo('body');
				$templateHtml = templateHtml;
			}
			else
			{
				if (typeof(templateHtml) == 'string')
				{
					while (regexMatch = regex.exec(templateHtml))
					{
						scripts.push(regexMatch[1]);
					}
					templateHtml = templateHtml.replace(regex, '');
				}

				$templateHtml = $(templateHtml);

				// add a header to the overlay, unless instructed otherwise
				if (!$templateHtml.is('.NoAutoHeader'))
				{
					if (extraOptions && extraOptions.title)
					{
						$('<h2 class="heading h1" />')
							.html(extraOptions.title)
							.prependTo($templateHtml);
					}
				}

				// add a cancel button to the overlay, if the overlay is a .formOverlay, has a .submitUnit but has no :reset button
				if ($templateHtml.is('.formOverlay'))
				{
					if ($templateHtml.find('.submitUnit').length)
					{
						if (!$templateHtml.find('.submitUnit :reset').length)
						{
							$templateHtml.find('.submitUnit .button:last')
								.after($('<input type="reset" class="button OverlayCloser" />').val(XenForo.phrases.cancel))
								.after(' ');
						}
					}
				}

				// create an overlay container, add the activated template to it and append it to the body.
				$overlay = $('<div class="xenOverlay __XenForoActivator" />')
					.appendTo('body')
					.addClass($(templateHtml).data('overlayclass')) // if content defines data-overlayClass, apply the value to the overlay as a class.
					.append($templateHtml);

				if (scripts.length)
				{
					for (i = 0; i < scripts.length; i++)
					{
						$.globalEval(scripts[i]);
					}
				}

				$overlay.xfActivate();
			}

			if (extraOptions)
			{
				// add {effect}Effect class to overlay container if necessary
				if (extraOptions.effect)
				{
					$overlay.addClass(extraOptions.effect + 'Effect');
				}

				// add any extra class name defined in extraOptions
				if (extraOptions.className)
				{
					$overlay.addClass(extraOptions.className);
					delete(extraOptions.className);
				}

				if (extraOptions.noCache)
				{
					extraOptions.onClose = function()
					{
						this.getOverlay().empty().remove();
					};
				}
			}

			// add an overlay closer if one does not already exist
			if ($overlay.find('.OverlayCloser').length == 0)
			{
				$overlay.prepend('<a class="close OverlayCloser"></a>');
			}

			$overlay.find('.OverlayCloser').click(function(e) { e.stopPropagation(); });

			// if no trigger was specified (automatic popup), then activate the overlay instead of the trigger
			$trigger = $trigger || $overlay;

			var windowHeight = $(window).height();

			var fixed = !(
				($.browser.msie && $.browser.version <= 6) // IE6 doesn't support position: fixed;
				|| XenForo.isTouchBrowser()
				|| $(window).width() <= 600 // overlay might end up especially tall
				|| windowHeight <= 550
				|| $overlay.outerHeight() >= .9 * windowHeight
			);
			if ($templateHtml.is('.NoFixedOverlay'))
			{
				fixed = false;
			}

			// activate the overlay
			$trigger.overlay($.extend(true,
			{
				target: $overlay,
				oneInstance: true,
				close: '.OverlayCloser',
				speed: XenForo.speed.normal,
				closeSpeed: XenForo.speed.fast,
				mask:
				{
					color: 'white',
					opacity: 0.6,
					loadSpeed: XenForo.speed.normal,
					closeSpeed: XenForo.speed.fast
				},
				fixed: fixed

			}, XenForo._overlayConfig, extraOptions));

			$trigger.bind(
			{
				onBeforeLoad: function(e)
				{
					$(document).triggerHandler('OverlayOpening');
				},
				onLoad: function(e)
				{
					var api = $(this).data('overlay'),
						$overlay = api.getOverlay(),
						scroller = $overlay.find('.OverlayScroller').get(0),
						resizeClose = null;

					if ($overlay.css('position') == 'absolute')
					{
						$overlay.find('.overlayScroll').removeClass('overlayScroll');
					}

					// timeout prevents flicker in FF
					if (scroller)
					{
						setTimeout(function()
						{
							scroller.scrollIntoView(true);
						}, 0);
					}

					// autofocus the first form element in a .formOverlay
					var $focus = $overlay.find('form').find('input[autofocus], textarea[autofocus], select[autofocus], .AutoFocus').first();
					if ($focus.length)
					{
						$focus.focus();
					}
					else
					{
						$overlay.find('form').find('input:not([type=hidden], [type=file]), textarea, select, button, .submitUnit a.button').first().focus();
					}

					// hide on window resize
					if (api.getConf().closeOnResize)
					{
						resizeClose = function()
						{
							console.info('Window resize, close overlay!');
							api.close();
						};

						$(window).one('resize', resizeClose);

						// remove event when closing the overlay
						$trigger.one('onClose', function()
						{
							$(window).unbind('resize', resizeClose);
						});
					}

					$(document).triggerHandler('OverlayOpened');
				},
				onBeforeClose: function(e)
				{
					$overlay.find('.Popup').each(function()
					{
						var PopupMenu = $(this).data('XenForo.PopupMenu');
						if (PopupMenu.hideMenu)
						{
							PopupMenu.hideMenu(e, true);
						}
					});
				}
			});

			api = $trigger.data('overlay');
				  $overlay.data('overlay', api);

			return api;
		},

		/**
		 * Present the user with a pop-up, modal message that they must confirm
		 *
		 * @param string Message
		 * @param string Message type (error, info, redirect)
		 * @param integer Timeout (auto-close after this period)
		 * @param function Callback onClose
		 */
		alert: function(message, messageType, timeOut, onClose)
		{
			message = String(message || 'Unspecified error');

			var key = message.replace(/[^a-z0-9_]/gi, '_') + parseInt(timeOut),
				$overlayHtml;

			if (XenForo._OverlayCache[key] === undefined)
			{
				if (timeOut)
				{
					$overlayHtml = $(''
						+ '<div class="xenOverlay timedMessage">'
						+	'<div class="content baseHtml">'
						+		message
						+		'<span class="close"></span>'
						+	'</div>'
						+ '</div>');

					XenForo._OverlayCache[key] = $overlayHtml.appendTo('body').overlay({
						top: 0,
						effect: 'slideDownContentFade',
						speed: XenForo.speed.normal,
						oneInstance: false,
						onBeforeClose: (onClose ? onClose : null)
					}).data('overlay');
				}
				else
				{
					$overlayHtml = $(''
						+ '<div class="errorOverlay">'
						+ 	'<a class="close OverlayCloser"></a>'
						+ 	'<h2 class="heading">' + (messageType || XenForo.phrases.following_error_occurred) + '</h2>'
						+ 	'<div class="baseHtml errorDetails"></div>'
						+ '</div>'
					);
					$overlayHtml.find('div.errorDetails').html(message);
					XenForo._OverlayCache[key] = XenForo.createOverlay(null, $overlayHtml, {
						onLoad: function() { var el = $('input:button.close, button.close', document.getElementById(key)).get(0); if (el) { el.focus(); } },
						onClose: (onClose ? onClose : null)
					});
				}
			}

			XenForo._OverlayCache[key].load();

			if (timeOut)
			{
				setTimeout('XenForo._OverlayCache["' + key + '"].close()', timeOut);
			}

			return $overlayHtml;
		},

		/**
		 * Shows a mini timed alert message, much like the OS X notifier 'Growl'
		 *
		 * @param string message
		 * @param integer timeOut Leave empty for a sticky message
		 * @param jQuery Counter balloon
		 */
		stackAlert: function(message, timeOut, $balloon)
		{
			var $message = $('<li class="stackAlert DismissParent"><div class="stackAlertContent">'
				+ '<span class="helper"></span>'
				+ '<a class="DismissCtrl"></a>'
				+ '</div></li>'),

			$container = $('#StackAlerts');

			if (!$container.length)
			{
				$container = $('<ul id="StackAlerts"></ul>').appendTo('body');
			}

			if ((message instanceof jQuery) == false)
			{
				message = $('<span>' + message + '</span>');
			}

			message.appendTo($message.find('div.stackAlertContent'));

			function removeMessage()
			{
				$message.xfFadeUp(XenForo.speed.slow, function()
				{
					$(this).empty().remove();

					if (!$container.children().length)
					{
						$container.hide();
					}
				});
			}

			function removeMessageAndScroll(e)
			{
				if ($balloon && $balloon.length)
				{
					$balloon.get(0).scrollIntoView(true);
				}

				removeMessage();
			}

			$message
				.hide()
				.prependTo($container.show())
				.fadeIn(XenForo.speed.normal, function()
				{
					if (timeOut > 0)
					{
						setTimeout(removeMessage, timeOut);
					}
				});

			$message.find('a').click(removeMessageAndScroll);

			return $message;
		},

		/**
		 * Adjusts all animation speeds used by XenForo
		 *
		 * @param integer multiplier - set to 0 to disable all animation
		 */
		setAnimationSpeed: function(multiplier)
		{
			var ieSpeedAdjust, s, index;

			for (index in XenForo.speed)
			{
				s = XenForo.speed[index];

				if ($.browser.msie)
				{
					// if we are using IE, change the animation lengths for a smoother appearance
					if (s <= 100)
					{
						ieSpeedAdjust = 2;
					}
					else if (s > 800)
					{
						ieSpeedAdjust = 1;
					}
					else
					{
						ieSpeedAdjust = 1 + 100/s;
					}
					XenForo.speed[index] = s * multiplier * ieSpeedAdjust;
				}
				else
				{
					XenForo.speed[index] = s * multiplier;
				}
			}
		},

		/**
		 * Generates a unique ID for an element, if required
		 *
		 * @param object HTML element (optional)
		 *
		 * @return string Unique ID
		 */
		uniqueId: function(element)
		{
			if (!element)
			{
				return 'XenForoUniq' + XenForo._uniqueIdCounter++;
			}
			else
			{
				return $(element).uniqueId().attr('id');
			}
		},

		redirect: function(url)
		{
			url = XenForo.canonicalizeUrl(url);

			if (url == window.location.href)
			{
				window.location.reload();
			}
			else
			{
				window.location = url;

				var destParts = url.split('#'),
					srcParts = window.location.href.split('#');

				if (destParts[1]) // has a hash
				{
					if (destParts[0] == srcParts[0])
					{
						// destination has a hash, but going to the same page
						window.location.reload();
					}
				}
			}
		},

		canonicalizeUrl: function(url, baseHref)
		{
			if (url.indexOf('/') == 0)
			{
				return url;
			}
			else if (url.match(/^(https?:|ftp:|mailto:)/i))
			{
				return url;
			}
			else
			{
				if (!baseHref)
				{
					baseHref = XenForo.baseUrl();
				}
				if (typeof baseHref != 'string')
				{
					baseHref = '';
				}
				return baseHref + url;
			}
		},

		_baseUrl: false,

		baseUrl: function()
		{
			if (XenForo._baseUrl === false)
			{
				var b = document.createElement('a'), $base = $('base');
				b.href = '';

				XenForo._baseUrl = (b.href.match(/[^\x20-\x7f]/) && $base.length) ? $base.attr('href') : b.href;

				if (!$base.length)
				{
					XenForo._baseUrl = XenForo._baseUrl.replace(/\?.*$/, '').replace(/\/[^\/]*$/, '/');
				}
			}

			return XenForo._baseUrl;
		},

		/**
		 * Adds a trailing slash to a string if one is not already present
		 *
		 * @param string
		 */
		trailingSlash: function(string)
		{
			if (string.substr(-1) != '/')
			{
				string += '/';
			}

			return string;
		},

		/**
		 * Escapes a string so it can be inserted into a RegExp without altering special characters
		 *
		 * @param string
		 *
		 * @return string
		 */
		regexQuote: function(string)
		{
			return (string + '').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!<>\|\:])/g, "\\$1");
		},

		/**
		 * Escapes HTML into plain text
		 *
		 * @param string
		 *
		 * @return string
		 */
		htmlspecialchars: function(string)
		{
			return (String(string) || '')
				.replace(/&/g, '&amp;')
				.replace(/"/g, '&quot;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;');
		},

		/**
		 * Determines whether the current page is being viewed in RTL mode
		 *
		 * @return boolean
		 */
		isRTL: function()
		{
			if (XenForo.RTL === undefined)
			{
				var dir = $('html').attr('dir');
				XenForo.RTL = (dir && dir.toUpperCase() == 'RTL') ? true : false;
			}

			return XenForo.RTL;
		},

		/**
		 * Switches instances of 'left' with 'right' and vice-versa in the input string.
		 *
		 * @param string directionString
		 *
		 * @return string
		 */
		switchStringRTL: function(directionString)
		{
			if (XenForo.isRTL())
			{
				directionString = directionString.replace(/left/i, 'l_e_f_t');
				directionString = directionString.replace(/right/i, 'left');
				directionString = directionString.replace('l_e_f_t', 'right');
			}
			return directionString;
		},

		/**
		 * Switches the x-coordinate of the input offset array
		 * @param offsetArray
		 * @return string
		 */
		switchOffsetRTL: function(offsetArray)
		{
			if (XenForo.isRTL() && !isNaN(offsetArray[1]))
			{
				offsetArray[1] = offsetArray[1] * -1;
			}

			return offsetArray;
		},

		/**
		 * Checks whether or not a tag is a list container
		 *
		 * @param jQuery Tag
		 *
		 * @return boolean
		 */
		isListTag: function($tag)
		{
			return ($tag.tagName == 'ul' || $tag.tagName == 'ol');
		},

		/**
		 * Checks that the value passed is a numeric value, even if its actual type is a string
		 *
		 * @param mixed Value to be checked
		 *
		 * @return boolean
		 */
		isNumeric: function(value)
		{
			return (!isNaN(value) && (value - 0) == value && value.length > 0);
		},

		/**
		 * Helper to check that an attribute value is 'positive'
		 *
		 * @param scalar Value to check
		 *
		 * @return boolean
		 */
		isPositive: function(value)
		{
			switch (String(value).toLowerCase())
			{
				case 'on':
				case 'yes':
				case 'true':
				case '1':
					return true;

				default:
					return false;
			}
		},

		/**
		 * Converts the first character of a string to uppercase.
		 *
		 * @param string
		 *
		 * @return string
		 */
		ucfirst: function(string)
		{
			return string.charAt(0).toUpperCase() + string.substr(1);
		},

		/**
		 * Replaces any existing avatars for the given user on the page
		 *
		 * @param integer user ID
		 * @param array List of avatar urls for the user, keyed with size code
		 * @param boolean Include crop editor image
		 */
		updateUserAvatars: function(userId, avatarUrls, andEditor)
		{
			console.log('Replacing visitor avatars on page: %o', avatarUrls);

			$.each(avatarUrls, function(sizeCode, avatarUrl)
			{
				var sizeClass = '.Av' + userId + sizeCode + (andEditor ? '' : ':not(.AvatarCropControl)');

				// .avatar > img
				$(sizeClass).find('img').attr('src', avatarUrl);

				// .avatar > span.img
				$(sizeClass).find('span.img').css('background-image', 'url(' + avatarUrl + ')');
			});
		},

		getEditorInForm: function(form, extraConstraints)
		{
			var $form = $(form),
				$textarea = $form.find('textarea.MessageEditor' + (extraConstraints || '')).first();

			if ($textarea.length)
			{
				if ($textarea.prop('disabled'))
				{
					return $form.find('.bbCodeEditorContainer textarea' + (extraConstraints || ''));
				}
				else if ($textarea.data('redactor'))
				{
					return $textarea.data('redactor');
				}
				else
				{
					return $textarea;
				}
			}

			return false;
		},

		/**
		 * Returns the name of the tag that should be animated for page scrolling
		 *
		 * @return string
		 */
		getPageScrollTagName: function()
		{
			//TODO: watch for webkit support for scrolling 'html'
			return ($.browser.webkit ? 'body' : 'html');
		},

		/**
		 * Determines whether or not we are working with a touch-based browser
		 *
		 * @return boolean
		 */
		isTouchBrowser: isTouchBrowser,

		/**
		 * Lazy-loads Javascript files
		 */
		scriptLoader:
		{
			loadScript: function(url, success, failure)
			{
				if (XenForo._loadedScripts[url] === undefined)
				{
					if (/tiny_mce[a-zA-Z0-9_-]*\.js/.test(url))
					{
						var preInit = {suffix: '', base: '', query: ''},
							baseHref = XenForo.baseUrl();

						if (/_(src|dev)\.js/g.test(url))
						{
							preInit.suffix = '_src';
						}

						if ((p = url.indexOf('?')) != -1)
						{
							preInit.query = url.substring(p + 1);
						}

						preInit.base = url.substring(0, url.lastIndexOf('/'));

						if (baseHref && preInit.base.indexOf('://') == -1 && preInit.base.indexOf('/') !== 0)
							preInit.base = baseHref + preInit.base;
					}

					$.ajax(
					{
						type: 'GET',
						url: url,
						cache: true,
						dataType: 'script',
						error: failure,
						success: function(javascript, textStatus)
						{
							XenForo._loadedScripts[url] = true;
							//$.globalEval(javascript);
							success.call();
						}
					});
				}
				else
				{
					success.call();
				}
			},

			loadCss: function(css, urlTemplate, success, failure)
			{
				var stylesheets = [],
					url;

				// build a list of stylesheets we have not already loaded
				$.each(css, function(i, stylesheet)
				{
					if (!XenForo._loadedScripts[stylesheet])
					{
						stylesheets.push(stylesheet);
					}
				});

				// if there are any left, construct the URL and load them
				if (stylesheets.length)
				{
					url = urlTemplate.replace('__sentinel__', stylesheets.join(','));
					url = XenForo.canonicalizeUrl(url, XenForo.ajaxBaseHref);

					$.ajax(
					{
						type: 'GET',
						url: url,
						cache: true,
						dataType: 'text',
						error: failure,
						success: function(cssText, textStatus)
						{
							$.each(stylesheets, function(i, stylesheet)
							{
								console.log('Loaded css %d, %s', i, stylesheet);
								XenForo._loadedScripts[stylesheet] = true;
							});

							var baseHref = XenForo.baseUrl();
							if (baseHref)
							{
								cssText = cssText.replace(
									/(url\(("|')?)([^"')]+)(("|')?\))/gi,
									function(all, front, null1, url, back, null2)
									{
										if (!url.match(/^(https?:|\/)/i))
										{
											url = baseHref + url;
										}
										return front + url + back;
									}
								);
							}

							$('<style>' + cssText + '</style>').appendTo('head');

							success.call();
						}
					});
				}
				else
				{
					success.call();
				}
			}
		}
	});

	// *********************************************************************

	/**
	 * Loads the requested list of javascript and css files
	 * Before firing the specified callback.
	 *
	 * @param array Javascript URLs
	 * @param array CSS URLs
	 * @param function Success callback
	 * @param function Error callback
	 */
	XenForo.ExtLoader = function(data, success, failure) { this.__construct(data, success, failure); };
	XenForo.ExtLoader.prototype =
	{
		__construct: function(data, success, failure)
		{
			this.success = success;
			this.failure = failure;
			this.totalFetched = 0;
			this.data = data;

			var numJs = 0,
				hasCss = 0,
				i = 0;

			// check if css is required, and make sure the format is good
			if (data.css && !$.isEmptyObject(data.css.stylesheets))
			{
				if (!data.css.urlTemplate)
				{
					return console.warn('Unable to load CSS without a urlTemplate being provided.');
				}

				hasCss = 1;
			}

			// check if javascript is required, and make sure the format is good
			if (data.js)
			{
				numJs = data.js.length;
			}

			this.totalExt = hasCss + numJs;

			// nothing to do
			if (!this.totalExt)
			{
				return this.callSuccess();
			}

			// fetch required javascript
			if (numJs)
			{
				for (i = 0; i < numJs; i++)
				{
					XenForo.scriptLoader.loadScript(data.js[i], $.context(this, 'successCount'), $.context(this, 'callFailure'));
				}
			}

			// fetch required css
			if (hasCss)
			{
				XenForo.scriptLoader.loadCss(data.css.stylesheets, data.css.urlTemplate, $.context(this, 'successCount'), $.context(this, 'callFailure'));
			}
		},

		/**
		 * Fires the success callback
		 */
		callSuccess: function()
		{
			if (typeof this.success == 'function')
			{
				this.success(this.data);
			}
		},

		/**
		 * Fires the error callback
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 * @param boolean errorThrown
		 */
		callFailure: function(ajaxData, textStatus, errorThrown)
		{
			if (!this.failed)
			{
				if (typeof this.failure == 'function')
				{
					this.failure(this.data);
				}
				else
				{
					console.warn('ExtLoader Failure %s %s', textStatus, ajaxData.status);
				}

				this.failed = true;
			}
		},

		/**
		 * Increment the totalFetched variable, and
		 * fire callSuccess() when this.totalFetched
		 * equals this.totalExt
		 *
		 * @param event e
		 */
		successCount: function(e)
		{
			this.totalFetched++;

			if (this.totalFetched >= this.totalExt)
			{
				this.callSuccess();
			}
		}
	};

	// *********************************************************************

	/**
	 * Instance of XenForo.TimestampRefresh
	 *
	 * @var XenForo.TimestampRefresh
	 */
	XenForo._TimestampRefresh = null;

	/**
	 * Allows date/time stamps on the page to be displayed as relative to now, and auto-refreshes periodically
	 */
	XenForo.TimestampRefresh = function() { this.__construct(); };
	XenForo.TimestampRefresh.prototype =
	{
		__construct: function()
		{
			this.active = this.activate();

			$(document).bind('XenForoWindowFocus', $.context(this, 'focus'));
		},

		/**
		 * Runs on window.focus, activates the system if deactivated
		 *
		 * @param event e
		 */
		focus: function(e)
		{
			if (!this.active)
			{
				this.activate(true);
			}
		},

		/**
		 * Runs a refresh, then refreshes again every 60 seconds
		 *
		 * @param boolean Refresh instantly
		 *
		 * @return integer Refresh interval or something...
		 */
		activate: function(instant)
		{
			if (instant)
			{
				this.refresh();
			}

			return this.active = window.setInterval($.context(this, 'refresh'), 60 * 1000); // one minute
		},

		/**
		 * Halts timestamp refreshes
		 *
		 * @return boolean false
		 */
		deactivate: function()
		{
			window.clearInterval(this.active);
			return this.active = false;
		},

		/**
		 * Date/Time output updates
		 */
		refresh: function(element, force)
		{
			if (!XenForo._hasFocus && !force)
			{
				return this.deactivate();
			}

			if ($.browser.msie && $.browser.version <= 6)
			{
				return;
			}

			var $elements = $('abbr.DateTime[data-time]', element),
				pageOpenTime = (new Date().getTime() / 1000),
				pageOpenLength = pageOpenTime - XenForo._pageLoadTime,
				serverTime = XenForo.serverTimeInfo.now,
				today = XenForo.serverTimeInfo.today,
				todayDow = XenForo.serverTimeInfo.todayDow,
				yesterday, week, dayOffset,
				i, $element, thisTime, thisDiff, thisServerTime, interval, calcDow;

			if (serverTime + pageOpenLength > today + 86400)
			{
				// day has changed, need to adjust
				dayOffset = Math.floor((serverTime + pageOpenLength - today) / 86400);

				today += dayOffset * 86400;
				todayDow = (todayDow + dayOffset) % 7;
			}

			yesterday = today - 86400;
			week = today - 6 * 86400;

			var rtlMarker = XenForo.isRTL() ? '\u200F' : '';

			for (i = 0; i < $elements.length; i++)
			{
				$element = $($elements[i]);

				// set the original value of the tag as its title
				if (!$element.attr('title'))
				{
					$element.attr('title', $element.text());
				}

				thisDiff = parseInt($element.data('diff'), 10);
				thisTime = parseInt($element.data('time'), 10);

				thisServerTime = thisTime + thisDiff;
				if (thisServerTime > serverTime + pageOpenLength)
				{
					thisServerTime = Math.floor(serverTime + pageOpenLength);
				}
				interval = serverTime - thisServerTime + thisDiff + pageOpenLength;

				if (interval < 0)
				{
					// date in the future
				}
				else if (interval <= 60)
				{
					$element.text(XenForo.phrases.a_moment_ago);
				}
				else if (interval <= 120)
				{
					$element.text(XenForo.phrases.one_minute_ago);
				}
				else if (interval < 3600)
				{
					$element.text(XenForo.phrases.x_minutes_ago
						.replace(/%minutes%/, Math.floor(interval / 60)));
				}
				else if (thisTime >= today)
				{
					$element.text(XenForo.phrases.today_at_x
						.replace(/%time%/, $element.attr('data-timestring'))); // must use attr for string value
				}
				else if (thisTime >= yesterday)
				{
					$element.text(XenForo.phrases.yesterday_at_x
							.replace(/%time%/, $element.attr('data-timestring'))); // must use attr for string value
				}
				else if (thisTime >= week)
				{
					calcDow = todayDow - Math.ceil((today - thisTime) / 86400);
					if (calcDow < 0)
					{
						calcDow += 7;
					}

					$element.text(rtlMarker + XenForo.phrases.day_x_at_time_y
						.replace('%day%', XenForo.phrases['day' + calcDow])
						.replace(/%time%/, $element.attr('data-timestring')) // must use attr for string value
					);
				}
				else
				{
					$element.text(rtlMarker + $element.attr('data-datestring')); // must use attr for string value
				}
			}
		}
	};

	// *********************************************************************

	/**
	 * Periodically refreshes all CSRF tokens on the page
	 */
	XenForo.CsrfRefresh = function() { this.__construct(); };
	XenForo.CsrfRefresh.prototype =
	{
		__construct: function()
		{
			this.activate();

			$(document).bind('XenForoWindowFocus', $.context(this, 'focus'));
		},

		/**
		 * Runs on window focus, activates the system if deactivated
		 *
		 * @param event e
		 */
		focus: function(e)
		{
			if (!this.active)
			{
				this.activate(true);
			}
		},

		/**
		 * Runs a refresh, then refreshes again every hour
		 *
		 * @param boolean Refresh instantly
		 *
		 * @return integer Refresh interval or something...
		 */
		activate: function(instant)
		{
			if (instant)
			{
				this.refresh();
			}

			this.active = window.setInterval($.context(this, 'refresh'), 50 * 60 * 1000); // 50 minutes
			return this.active;
		},

		/**
		 * Halts csrf refreshes
		 */
		deactivate: function()
		{
			window.clearInterval(this.active);
			this.active = false;
		},

		/**
		 * Updates all CSRF tokens
		 */
		refresh: function()
		{
			if (!XenForo._csrfRefreshUrl)
			{
				return;
			}

			if (!XenForo._hasFocus)
			{
				this.deactivate();
				return;
			}

			XenForo.ajax(
				XenForo._csrfRefreshUrl,
				'',
				function(ajaxData, textStatus)
				{
					if (!ajaxData || ajaxData.csrfToken === undefined)
					{
						return false;
					}

					var tokenInputs = $('input[name=_xfToken]').val(ajaxData.csrfToken);

					XenForo._csrfToken = ajaxData.csrfToken;

					if (tokenInputs.length)
					{
						console.log('XenForo CSRF token updated in %d places (%s)', tokenInputs.length, ajaxData.csrfToken);
					}

					$(document).trigger(
					{
						type: 'CSRFRefresh',
						ajaxData: ajaxData
					});
				},
				{ error: false, global: false }
			);
		}
	};

	// *********************************************************************

	/**
	 * Stores the id of the currently active popup menu group
	 *
	 * @var string
	 */
	XenForo._PopupMenuActiveGroup = null;

	/**
	 * Popup menu system.
	 *
	 * Requires:
	 * <el class="Popup">
	 * 		<a rel="Menu">control</a>
	 * 		<el class="Menu {Left} {Hider}">menu content</el>
	 * </el>
	 *
	 * * .Menu.Left causes orientation of menu to reverse, away from scrollbar
	 * * .Menu.Hider causes menu to appear over control instead of below
	 *
	 * @param jQuery *.Popup container element
	 */
	XenForo.PopupMenu = function($container) { this.__construct($container); };
	XenForo.PopupMenu.prototype =
	{
		__construct: function($container)
		{
			// the container holds the control and the menu
			this.$container = $container;

			// take the menu, which will be a sibling of the control, and append/move it to the end of the body
			this.$menu = this.$container.find('.Menu').appendTo('body');
			this.$menu.data('XenForo.PopupMenu', this);
			this.menuVisible = false;

			// check that we have the necessary elements
			if (!this.$menu.length)
			{
				console.warn('Unable to find menu for Popup %o', this.$container);

				return false;
			}

			// add a unique id to the menu
			this.$menu.id = XenForo.uniqueId(this.$menu);

			// variables related to dynamic content loading
			this.contentSrc = this.$menu.data('contentsrc');
			this.contentDest = this.$menu.data('contentdest');
			this.loading = null;
			this.unreadDisplayTimeout = null;
			this.newlyOpened = false;

			// bind events to the menu control
			this.$clicker = $container.find('[rel="Menu"]').first().click($.context(this, 'controlClick'));

			if (!XenForo.isTouchBrowser())
			{
				this.$clicker.mouseover($.context(this, 'controlHover')).hoverIntent(
				{
					sensitivity: 1,
					interval: 100,
					timeout: 0,
					over: $.context(this, 'controlHoverIntent'),
					out: function(){}
				});
			}

			this.$control = this.addPopupGadget(this.$clicker);

			// the popup group for this menu, if specified
			this.popupGroup = this.$control.closest('[data-popupgroup]').data('popupgroup');

			//console.log('Finished popup menu for %o', this.$control);
		},

		addPopupGadget: function($control)
		{
			if (!$control.hasClass('NoPopupGadget') && !$control.hasClass('SplitCtrl'))
			{
				$control.append('<span class="arrowWidget" />');
			}

			var $popupControl = $control.closest('.PopupControl');
			if ($popupControl.length)
			{
				$control = $popupControl.addClass('PopupContainerControl');
			}

			$control.addClass('PopupControl');

			return $control;
		},

		/**
		 * Opens or closes a menu, or navigates to another page, depending on menu status and control attributes.
		 *
		 * Clicking a control while the menu is hidden will open and show the menu.
		 * If the control has an href attribute, clicking on it when the menu is open will navigate to the specified URL.
		 * If the control does not have an href, a click will close the menu.
		 *
		 * @param event
		 *
		 * @return mixed
		 */
		controlClick: function(e)
		{
			console.debug('%o control clicked. NewlyOpened: %s, Animated: %s', this.$control, this.newlyOpened, this.$menu.is(':animated'));

			if (!this.newlyOpened && !this.$menu.is(':animated'))
			{
				console.info('control: %o', this.$control);

				if (this.$menu.is(':hidden'))
				{
					this.showMenu(e, false);
				}
				else if (this.$clicker.attr('href') && !XenForo.isPositive(this.$clicker.data('closemenu')))
				{
					console.warn('Following hyperlink from %o', this.$clicker);
					return true;
				}
				else
				{
					this.hideMenu(e, false);
				}
			}
			else
			{
				console.debug('Click on control of newly-opened or animating menu, ignored');
			}

			e.preventDefault();
			e.target.blur();
			return false;
		},

		/**
		 * Handles hover events on menu controls. Will normally do nothing,
		 * unless there is a menu open and the control being hovered belongs
		 * to the same popupGroup, in which case this menu will open instantly.
		 *
		 * @param event
		 *
		 * @return mixed
		 */
		controlHover: function(e)
		{
			if (this.popupGroup != null && this.popupGroup == this.getActiveGroup())
			{
				this.showMenu(e, true);

				return false;
			}
		},

		/**
		 * Handles hover-intent events on menu controls. Menu will show
		 * if the cursor is hovered over a control at low speed and for a duration
		 *
		 * @param event
		 */
		controlHoverIntent: function(e)
		{
			var instant = false;//(this.popupGroup != null && this.popupGroup == this.getActiveGroup());

			if (this.$clicker.hasClass('SplitCtrl'))
			{
				instant = true;
			}

			this.showMenu(e, instant);
		},

		/**
		 * Opens and shows a popup menu.
		 *
		 * If the menu requires dynamic content to be loaded, this will load the content.
		 * To define dynamic content, the .Menu element should have:
		 * * data-contentSrc = URL to JSON that contains templateHtml to be inserted
		 * * data-contentDest = jQuery selector specifying the element to which the templateHtml will be appended. Defaults to this.$menu.
		 *
		 * @param event
		 * @param boolean Show instantly (true) or fade in (false)
		 */
		showMenu: function(e, instant)
		{
			if (this.$menu.is(':visible'))
			{
				return false;
			}

			//console.log('Show menu event type = %s', e.type);

			var $eShow = new $.Event('PopupMenuShow');
			$eShow.$menu = this.$menu;
			$eShow.instant = instant;
			$(document).trigger($eShow);

			if ($eShow.isDefaultPrevented())
			{
				return false;
			}

			this.menuVisible = true;

			this.setMenuPosition('showMenu');

			if (this.$menu.hasClass('BottomControl'))
			{
				instant = true;
			}

			if (this.contentSrc && !this.loading)
			{
				this.loading = XenForo.ajax(
					this.contentSrc, '',
					$.context(this, 'loadSuccess'),
					{ type: 'GET' }
				);

				this.$menu.find('.Progress').addClass('InProgress');

				instant = true;
			}

			this.setActiveGroup();

			this.$control.addClass('PopupOpen').removeClass('PopupClosed');

			this.$menu.stop().xfSlideDown((instant ? 0 : XenForo.speed.xfast), $.context(this, 'menuShown'));

			if (!this.menuEventsInitialized)
			{
				var $html = $('html'),
					t = this,
					htmlSize = [$html.width(), $html.height()];

				// TODO: make this global?
				// TODO: touch interfaces don't like this
				$(document).bind({
					PopupMenuShow: $.context(this, 'hideIfOther')
				});

				// Webkit mobile kinda does not support document.click, bind to other elements
				if (XenForo._isWebkitMobile)
				{
					$(document.body.children).click($.context(this, 'hideMenu'));
				}
				else
				{
					$(document).click($.context(this, 'hideMenu'));
				}

				$(document).on('HideAllMenus', function(e)
				{
					if (t.menuVisible)
					{
						t._hideMenu(e, true);
					}
				});

				$(window).bind(
				{
					resize: function(e) {
						// only trigger close if the window size actually changed - some mobile browsers trigger without size change
						var w = $html.width(), h = $html.height();
						if (w != htmlSize[0] || h != htmlSize[1])
						{
							htmlSize[0] = w; htmlSize[1] = h;
							t._hideMenu(e);
						}
					}
				});

				this.$menu.delegate('a', 'click', $.context(this, 'menuLinkClick'));
				this.$menu.delegate('.MenuCloser', 'click', $.context(this, 'hideMenu'));

				this.menuEventsInitialized = true;
			}
		},

		/**
		 * Hides an open popup menu (conditionally)
		 *
		 * @param event
		 * @param boolean Hide instantly (true) or fade out (false)
		 */
		hideMenu: function(e, instant)
		{
			if (this.$menu.is(':visible') && this.triggersMenuHide(e))
			{
				this._hideMenu(e, !instant);
			}
		},

		/**
		 * Hides an open popup menu, without checking context or environment
		 *
		 * @param event
		 * @param boolean Fade out the menu (true) or hide instantly out (false)
		 */
		_hideMenu: function(e, fade)
		{
			//console.log('Hide menu \'%s\' %o TYPE = %s', this.$control.text(), this.$control, e.type);
			this.menuVisible = false;

			this.setActiveGroup(null);

			if (this.$menu.hasClass('BottomControl'))
			{
				fade = false;
			}

			// stop any unread content fading into its read state
			clearTimeout(this.unreadDisplayTimeout);
			this.$menu.find('.Unread').stop();

			this.$menu.xfSlideUp((fade ? XenForo.speed.xfast : 0), $.context(this, 'menuHidden'));
		},

		/**
		 * Fires when the menu showing animation is completed and the menu is displayed
		 */
		menuShown: function()
		{
			// if the menu has a data-contentSrc attribute, we can assume that it requires dynamic content, which has not yet loaded
			var contentLoaded = (this.$menu.data('contentsrc') ? false : true),
				$input = null;

			this.$control.addClass('PopupOpen').removeClass('PopupClosed');

			this.newlyOpened = true;
			setTimeout($.context(function()
			{
				this.newlyOpened = false;
			}, this), 50);

			this.$menu.trigger('ShowComplete', [contentLoaded]);

			this.setMenuPosition('menuShown');

			this.highlightUnreadContent();

			if (!XenForo.isTouchBrowser())
			{
				$input = this.$menu.find('input[type=text], input[type=search], textarea, select').first();
				if ($input.length)
				{
					if ($input.data('nofocus'))
					{
						return;
					}

					$input.select();
				}
			}
		},

		/**
		 * Fires when the menu hiding animations is completed and the menu is hidden
		 */
		menuHidden: function()
		{
			this.$control.removeClass('PopupOpen').addClass('PopupClosed');

			this.$menu.trigger('MenuHidden');
		},

		/**
		 * Fires in response to the document triggering 'PopupMenuShow' and hides the current menu
		 * if the menu that fired the event is not itself.
		 *
		 * @param event
		 */
		hideIfOther: function(e)
		{
			if (e.$menu.prop($.expando) != this.$menu.prop($.expando))
			{
				this.hideMenu(e, e.instant);
			}
		},

		/**
		 * Checks to see if an event should hide the menu.
		 *
		 * Returns false if:
		 * * Event target is a child of the menu, or is the menu itself
		 *
		 * @param event
		 *
		 * @return boolean
		 */
		triggersMenuHide: function(e)
		{
			var $target = $(e.target);

			if (e.ctrlKey || e.shiftKey || e.altKey)
			{
				return false;
			}

			if (e.which > 1)
			{
				// right or middle click, don't close
				return false;
			}

			if ($target.is('.MenuCloser'))
			{
				return true;
			}

			// is the control a hyperlink that has not had its default action prevented?
			if ($target.is('a[href]') && !e.isDefaultPrevented())
			{
				return true;
			}

			if (e.target === document || !$target.closest('#' + this.$menu.id).length)
			{
				return true;
			}

			return false;
		},

		/**
		 * Sets the position of the popup menu, based on the position of the control
		 */
		setMenuPosition: function(caller)
		{
			//console.info('setMenuPosition(%s)', caller);

			var $controlParent,
				controlLayout, // control coordinates
				menuLayout, // menu coordinates
				contentLayout, // #content coordinates
				$content,
				$window,
				proposedLeft,
				proposedTop;

			controlLayout = this.$control.coords('outer');

			this.$menu.css('position', '').removeData('position');

			$controlParent = this.$control;
			while ($controlParent && $controlParent.length && $controlParent.get(0) != document)
			{
				if ($controlParent.css('position') == 'fixed')
				{
					controlLayout.top -= $(window).scrollTop();
					controlLayout.left -= $(window).scrollLeft();

					this.$menu.css('position', 'fixed').data('position', 'fixed');
					break;
				}

				$controlParent = $controlParent.parent();
			}

			this.$control.removeClass('BottomControl');

			// set the menu to sit flush with the left of the control, immediately below it
			this.$menu.removeClass('BottomControl').css(
			{
				left: controlLayout.left,
				top: controlLayout.top + controlLayout.height - 1 // fixes a weird thing where the menu doesn't join the control
			});

			menuLayout = this.$menu.coords('outer');

			$content = $('#content .pageContent');
			if ($content.length)
			{
				contentLayout = $content.coords('outer');
			}
			else
			{
				contentLayout = $('body').coords('outer');
			}

			$window = $(window);
			var sT = $window.scrollTop(),
				sL = $window.scrollLeft(),
				windowWidth = $window.width();

			/*
			 * if the menu's right edge is off the screen, check to see if
			 * it would be better to position it flush with the right edge of the control.
			 * RTL displays will try to do this if possible.
			 */
			if (XenForo.isRTL() || menuLayout.left + menuLayout.width > contentLayout.left + contentLayout.width)
			{
				proposedLeft = Math.max(0, controlLayout.left + controlLayout.width - menuLayout.width);
				if (proposedLeft > sL)
				{
					this.$menu.css('left', proposedLeft);
				}
			}

			if (parseInt(this.$menu.css('left'), 10) + menuLayout.width > windowWidth + sL)
			{
				this.$menu.css('left', 0);
			}

			/*
			 * if the menu's bottom edge is off the screen, check to see if
			 * it would be better to position it above the control
			 */
			if (menuLayout.top + menuLayout.height > $window.height() + sT)
			{
				proposedTop = controlLayout.top - menuLayout.height;
				if (proposedTop > sT)
				{
					this.$control.addClass('BottomControl');
					this.$menu.addClass('BottomControl');
					this.$menu.css('top', controlLayout.top - this.$menu.outerHeight());
				}
			}
		},

		/**
		 * Fires when dynamic content for a popup menu has been loaded.
		 *
		 * Checks for errors and if there are none, appends the new HTML to the element selected by this.contentDest.
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 */
		loadSuccess: function(ajaxData, textStatus)
		{
			if (XenForo.hasResponseError(ajaxData) || !XenForo.hasTemplateHtml(ajaxData))
			{
				return false;
			}

			// check for content destination
			if (!this.contentDest)
			{
				console.warn('Menu content destination not specified, using this.$menu.');

				this.contentDest = this.$menu;
			}

			console.info('Content destination: %o', this.contentDest);

			var self = this;

			new XenForo.ExtLoader(ajaxData, function(data) {
				self.$menu.trigger('LoadComplete');

				var $templateHtml = $(data.templateHtml);

				// append the loaded content to the destination
				$templateHtml.xfInsert(
					self.$menu.data('insertfn') || 'appendTo',
					self.contentDest,
					'slideDown', 0,
					function()
					{
						self.$menu.css('min-width', '199px');
						setTimeout(function() {
							self.$menu.css('min-width', '');
						}, 0);
						if (self.$control.hasClass('PopupOpen'))
						{
							self.menuShown();
						}
					}
				);

				self.$menu.find('.Progress').removeClass('InProgress');
			});
		},

		resetLoader: function()
		{
			if (this.contentDest && this.loading)
			{
				delete(this.loading);
				$(this.contentDest).empty();
				this.$menu.find('.Progress').addClass('InProgress');
			}
		},

		menuLinkClick: function(e)
		{
			this.hideMenu(e, true);
		},

		/**
		 * Sets the name of the globally active popup group
		 *
		 * @param mixed If specified, active group will be set to this value.
		 *
		 * @return string Active group name
		 */
		setActiveGroup: function(value)
		{
			var activeGroup = (value === undefined ? this.popupGroup : value);

			return XenForo._PopupMenuActiveGroup = activeGroup;
		},

		/**
		 * Returns the name of the globally active popup group
		 *
		 * @return string Active group name
		 */
		getActiveGroup: function()
		{
			return XenForo._PopupMenuActiveGroup;
		},

		/**
		 * Fade return the background color of unread items to the normal background
		 */
		highlightUnreadContent: function()
		{
			var $unreadContent = this.$menu.find('.Unread'),
				defaultBackground = null,
				counterSelector = null;

			if ($unreadContent.length)
			{
				defaultBackground = $unreadContent.data('defaultbackground');

				if (defaultBackground)
				{
					$unreadContent.css('backgroundColor', null);

					this.unreadDisplayTimeout = setTimeout($.context(function()
					{
						// removes an item specified by data-removeCounter on the menu element
						if (counterSelector = this.$menu.data('removecounter'))
						{
							XenForo.balloonCounterUpdate($(counterSelector), 0);
						}

						$unreadContent.animate({ backgroundColor: defaultBackground }, 2000, $.context(function()
						{
							$unreadContent.removeClass('Unread');
							this.$menu.trigger('UnreadDisplayComplete');
						}, this));
					}, this), 1000);
				}
			}
		}
	};

	// *********************************************************************

	/**
	 * Shows and hides global request pending progress indicators for AJAX calls.
	 *
	 * Binds to the global ajaxStart and ajaxStop jQuery events.
	 * Also binds to the PseudoAjaxStart and PseudoAjaxStop events,
	 * see XenForo.AutoInlineUploader
	 *
	 * Initialized by XenForo.init()
	 */
	XenForo.AjaxProgress = function()
	{
		var overlay = null,

		showOverlay = function()
		{
			// mini indicators
			$('.Progress, .xenForm .ctrlUnit.submitUnit dt').addClass('InProgress');

			// the overlay
			if (!overlay)
			{
				overlay = $('<div id="AjaxProgress" class="xenOverlay"><div class="content"><span class="close" /></div></div>')
					.appendTo('body')
					.overlay(
					{
						top: 0,
						speed: XenForo.speed.fast,
						oneInstance: false,
						closeOnClick: false,
						closeOnEsc: false
					}).data('overlay');
			}

			overlay.load();
		},

		hideOverlay = function()
		{
			// mini indicators
			$('.Progress, .xenForm .ctrlUnit.submitUnit dt')
				.removeClass('InProgress');

			// the overlay
			if (overlay && overlay.isOpened())
			{
				overlay.close();
			}
		};

		$(document).bind(
		{
			ajaxStart: function(e)
			{
				XenForo._AjaxProgress = true;
				showOverlay();
			},

			ajaxStop: function(e)
			{
				XenForo._AjaxProgress = false;
				hideOverlay();
			},

			PseudoAjaxStart: function(e)
			{
				showOverlay();
			},

			PseudoAjaxStop: function(e)
			{
				hideOverlay();
			}
		});

		if ($.browser.msie && $.browser.version < 7)
		{
			$(document).bind('scroll', function(e)
			{
				if (overlay && overlay.isOpened() && !overlay.getConf().fixed)
				{
					overlay.getOverlay().css('top', overlay.getConf().top + $(window).scrollTop());
				}
			});
		}
	};

	// *********************************************************************

	/**
	 * Handles the scrollable pagenav gadget, allowing selection of any page between 1 and (end)
	 * while showing only {range*2+1} pages plus first and last at once.
	 *
	 * @param jQuery .pageNav
	 */
	XenForo.PageNav = function($pageNav) { this.__construct($pageNav); };
	XenForo.PageNav.prototype =
	{
		__construct: function($pageNav)
		{
			if (XenForo.isRTL())
			{
				// scrollable doesn't support RTL yet
				return false;
			}

			var $scroller = $pageNav.find('.scrollable');
			if (!$scroller.length)
			{
				return false;
			}

			console.info('PageNav %o', $pageNav);

			this.start = parseInt($pageNav.data('start'));
			this.page  = parseInt($pageNav.data('page'));
			this.end   = parseInt($pageNav.data('end'));
			this.last  = parseInt($pageNav.data('last'));
			this.range = parseInt($pageNav.data('range'));
			this.size  = (this.range * 2 + 1);

			this.baseurl = $pageNav.data('baseurl');
			this.sentinel = $pageNav.data('sentinel');

			$scroller.scrollable(
			{
				speed: XenForo.speed.slow,
				easing: 'easeOutBounce',
				keyboard: false,
				prev: '#nullPrev',
				next: '#nullNext',
				touch: false
			});

			this.api = $scroller.data('scrollable').onBeforeSeek($.context(this, 'beforeSeek'));

			this.$prevButton = $pageNav.find('.PageNavPrev').click($.context(this, 'prevPage'));
			this.$nextButton = $pageNav.find('.PageNavNext').click($.context(this, 'nextPage'));

			this.setControlVisibility(this.api.getIndex(), 0);
		},

		/**
		 * Scrolls to the previous 'page' of page links, creating them if necessary
		 *
		 * @param Event e
		 */
		prevPage: function(e)
		{
			if (this.api.getIndex() == 0 && this.start > 2)
			{
				var i = 0,
					minPage = Math.max(2, (this.start - this.size));

				for (i = this.start - 1; i >= minPage; i--)
				{
					this.prepend(i);
				}

				this.start = minPage;
			}

			this.api.seekTo(Math.max(this.api.getIndex() - this.size, 0));
		},

		/**
		 * Scrolls to the next 'page' of page links, creating them if necessary
		 *
		 * @param Event e
		 */
		nextPage: function(e)
		{
			if ((this.api.getIndex() + 1 + 2 * this.size) > this.api.getSize() && this.end < this.last - 1)
			{
				var i = 0,
					maxPage = Math.min(this.last - 1, this.end + this.size);

				for (i = this.end + 1; i <= maxPage; i++)
				{
					this.append(i);
				}

				this.end = maxPage;
			}

			this.api.seekTo(Math.min(this.api.getSize() - this.size, this.api.getIndex() + this.size));
		},

		/**
		 * Adds an additional page link to the beginning of the scrollable section, out of sight
		 *
		 * @param integer page
		 */
		prepend: function(page)
		{
			this.buildPageLink(page).prependTo(this.api.getItemWrap());

			this.api.next(0);
		},

		/**
		 * Adds an additional page link to the end of the scrollable section, out of sight
		 *
		 * @param integer page
		 */
		append: function(page)
		{
			this.buildPageLink(page).appendTo(this.api.getItemWrap());
		},

		/**
		 * Buids a single page link
		 *
		 * @param integer page
		 *
		 * @return jQuery page link html
		 */
		buildPageLink: function(page)
		{
			return $('<a />',
			{
				href:  this.buildPageUrl(page),
				text:  page,
				'class': (page > 999 ? 'gt999' : '')
			});
		},

		/**
		 * Converts the baseUrl into a page url by replacing the sentinel value
		 *
		 * @param integer page
		 *
		 * @return string page URL
		 */
		buildPageUrl: function(page)
		{
			return this.baseurl
				.replace(this.sentinel, page)
				.replace(escape(this.sentinel), page);
		},

		/**
		 * Runs immediately before the pagenav seeks to a new index,
		 * Toggles visibility of the next/prev controls based on whether they are needed or not
		 *
		 * @param jQuery Event e
		 * @param integer index
		 */
		beforeSeek: function(e, index)
		{
			this.setControlVisibility(index, XenForo.speed.fast);
		},

		/**
		 * Sets the visibility of the scroll controls, based on whether using them would do anything
		 * (hide the prev-page control if on the first page, etc.)
		 *
		 * @param integer Target index of the current scroll
		 *
		 * @param mixed Speed of animation
		 */
		setControlVisibility: function(index, speed)
		{
			if (index == 0 && this.start <= 2)
			{
				this.$prevButton.hide(speed);
			}
			else
			{
				this.$prevButton.show(speed);
			}

			if (this.api.getSize() - this.size <= index && this.end >= this.last - 1)
			{
				this.$nextButton.hide(speed);
			}
			else
			{
				this.$nextButton.show(speed);
			}
		}
	};

	// *********************************************************************

	XenForo.ToggleTrigger = function($trigger) { this.__construct($trigger); };
	XenForo.ToggleTrigger.prototype =
	{
		__construct: function($trigger)
		{
			this.$trigger = $trigger;
			this.loaded = false;
			this.targetVisible = false;
			this.$target = null;

			if ($trigger.data('target'))
			{
				var anchor = $trigger.closest('.ToggleTriggerAnchor');
				if (!anchor.length)
				{
					anchor = $('body');
				}
				var target = anchor.find($trigger.data('target'));
				if (target.length)
				{
					this.$target = target;
					var toggleClass = target.data('toggle-class');
					this.targetVisible = toggleClass ? target.hasClass(toggleClass) : target.is(':visible');
				}
			}

			if ($trigger.data('only-if-hidden')
				&& XenForo.isPositive($trigger.data('only-if-hidden'))
				&& this.targetVisible
			)
			{
				return;
			}

			$trigger.click($.context(this, 'toggle'));
		},

		toggle: function(e)
		{
			e.preventDefault();

			var $trigger = this.$trigger,
				$target = this.$target;

			if ($trigger.data('toggle-if-pointer') && XenForo.isPositive($trigger.data('toggle-if-pointer')))
			{
				if ($trigger.css('cursor') !== 'pointer')
				{
					return;
				}
			}

			if ($trigger.data('toggle-text'))
			{
				var toggleText = $trigger.text();
				$trigger.text($trigger.data('toggle-text'));
				$trigger.data('toggle-text', toggleText);
			}

			if (e.pageX || e.pageY)
			{
				$trigger.blur();
			}

			if ($target)
			{
				$(document).trigger('ToggleTriggerEvent',
				{
					closing: this.targetVisible,
					$target: $target
				});
				
				this.hideSelfIfNeeded();

				var triggerTargetEvent = function() {
					$target.trigger('elementResized');
				};

				var toggleClass = $target.data('toggle-class');
				if (this.targetVisible)
				{					
					if (toggleClass)
					{
						$target.removeClass(toggleClass);
						triggerTargetEvent();
					}
					else
					{
						$target.xfFadeUp(null, triggerTargetEvent);
					}
				}
				else
				{
					if (toggleClass)
					{
						$target.addClass(toggleClass);
						triggerTargetEvent();
					}
					else
					{
						$target.xfFadeDown(null, triggerTargetEvent);
					}
				}
				this.targetVisible = !this.targetVisible;
			}
			else
			{
				this.load();
			}
		},

		hideSelfIfNeeded: function()
		{
			var hideSel = this.$trigger.data('hide');

			if (!hideSel)
			{
				return false;
			}

			var $el;

			if (hideSel == 'self')
			{
				$el = this.$trigger;
			}
			else
			{
				var anchor = this.$trigger.closest('.ToggleTriggerAnchor');
				if (!anchor.length)
				{
					anchor = $('body');
				}
				$el = anchor.find(hideSel);
			}

			$el.hide(); return;
			//$el.xfFadeUp();
		},

		load: function()
		{
			if (this.loading || !this.$trigger.attr('href'))
			{
				return;
			}

			var self = this;

			var $position = $(this.$trigger.data('position'));
			if (!$position.length)
			{
				$position = this.$trigger.closest('.ToggleTriggerAnchor');
				if (!$position.length)
				{
					console.warn("Could not match toggle target position selector %s", this.$trigger.data('position'));
					return false;
				}
			}

			var method = this.$trigger.data('position-method') || 'insertAfter';

			this.loading = true;

			XenForo.ajax(this.$trigger.attr('href'), {}, function(ajaxData) {
				self.loading = false;

				if (XenForo.hasResponseError(ajaxData))
				{
					return false;
				}

				// received a redirect rather than a view - follow it.
				if (ajaxData._redirectStatus && ajaxData._redirectTarget)
				{
					var fn = function()
					{
						XenForo.redirect(ajaxData._redirectTarget);
					};

					if (XenForo._manualDeferOverlay)
					{
						$(document).one('ManualDeferComplete', fn);
					}
					else
					{
						fn();
					}
					return false;
				}

				if (!ajaxData.templateHtml)
				{
					return false;
				}

				new XenForo.ExtLoader(ajaxData, function(data) {
					self.$target = $(data.templateHtml);

					self.$target.xfInsert(method, $position);
					self.targetVisible = true;
					self.hideSelfIfNeeded();
				});
			});
		}
	};

	// *********************************************************************

	/**
	 * Triggers an overlay from a regular link or button
	 * Triggers can provide an optional data-cacheOverlay attribute
	 * to allow multiple trigers to access the same overlay.
	 *
	 * @param jQuery .OverlayTrigger
	 */
	XenForo.OverlayTrigger = function($trigger, options) { this.__construct($trigger, options); };
	XenForo.OverlayTrigger.prototype =
	{
		__construct: function($trigger, options)
		{
			this.$trigger = $trigger.click($.context(this, 'show'));
			this.options = options;
		},

		/**
		 * Begins the process of loading and showing an overlay
		 *
		 * @param event e
		 */
		show: function(e)
		{
			var parentOverlay = this.$trigger.closest('.xenOverlay').data('overlay'),
				cache,
				options,
				isUserLink = (this.$trigger.is('.username, .avatar')),
				cardHref;

			if (!parseInt(XenForo._enableOverlays))
			{
				// if no overlays, use <a href /> by preference
				if (this.$trigger.attr('href'))
				{
					return true;
				}
				else if (this.$trigger.data('href'))
				{
					if (this.$trigger.closest('.AttachmentUploader, #AttachmentUploader').length == 0)
					{
						// open the overlay target as a regular link, unless it's the attachment uploader
						XenForo.redirect(this.$trigger.data('href'));
						return false;
					}
				}
				else
				{
					// can't do anything - should not happen
					console.warn('No alternative action found for OverlayTrigger %o', this.$trigger);
					return true;
				}
			}

			// abort if this is a username / avatar overlay with NoOverlay specified
			if (isUserLink && this.$trigger.hasClass('NoOverlay'))
			{
				return true;
			}

			// abort if the event has a modifier key
			if (e.ctrlKey || e.shiftKey || e.altKey)
			{
				return true;
			}

			// abort if the event is a middle or right-button click
			if (e.which > 1)
			{
				return true;
			}

			if (this.options && this.options.onBeforeTrigger)
			{
				var newE = $.Event();
				newE.clickEvent = e;
				this.options.onBeforeTrigger(newE);
				if (newE.isDefaultPrevented())
				{
					return;
				}
			}

			e.preventDefault();

			if (parentOverlay && parentOverlay.isOpened())
			{
				var self = this;
				parentOverlay.getTrigger().one('onClose', function(innerE) {
					setTimeout(function() {
						self.show(innerE);
					}, 0);
				});
				parentOverlay.getConf().mask.closeSpeed = 0;
				parentOverlay.close();
				return;
			}

			if (!this.OverlayLoader)
			{
				options = (typeof this.options == 'object' ? this.options : {});
				options = $.extend(options, this.$trigger.data('overlayoptions'));

				cache = this.$trigger.data('cacheoverlay');
				if (cache !== undefined)
				{
					if (XenForo.isPositive(cache))
					{
						cache = true;
					}
					else
					{
						cache = false;
						options.onClose = $.context(this, 'deCache');
					}
				}
				else if (this.$trigger.is('input:submit'))
				{
					cache = false;
					options.onClose = $.context(this, 'deCache');
				}

				if (isUserLink && !this.$trigger.hasClass('OverlayTrigger'))
				{
					if (!this.$trigger.data('cardurl') && this.$trigger.attr('href'))
					{
						cardHref = this.$trigger.attr('href').replace(/#.*$/, '');
						if (cardHref.indexOf('?') >= 0)
						{
							cardHref += '&card=1';
						}
						else
						{
							cardHref += '?card=1';
						}

						this.$trigger.data('cardurl', cardHref);
					}

					cache = true;
					options.speed = XenForo.speed.fast;
				}

				this.OverlayLoader = new XenForo.OverlayLoader(this.$trigger, cache, options);
				this.OverlayLoader.load();

				e.preventDefault();
				return true;
			}

			this.OverlayLoader.show();
		},

		deCache: function()
		{
			if (this.OverlayLoader && this.OverlayLoader.overlay)
			{
				console.info('DeCache %o', this.OverlayLoader.overlay.getOverlay());
				this.OverlayLoader.overlay.getTrigger().removeData('overlay');
				this.OverlayLoader.overlay.getOverlay().empty().remove();
			}
			delete(this.OverlayLoader);
		}
	};

	// *********************************************************************

	XenForo.LightBoxTrigger = function($link)
	{
		var containerSelector = '*[data-author]';

		new XenForo.OverlayTrigger($link.data('cacheoverlay', 1),
		{
			top: 15,
			speed: 1, // prevents the onLoad event being fired prematurely
			closeSpeed: 0,
			closeOnResize: true,
			mask:
			{
				color: 'rgb(0,0,0)',
				opacity: 0.6,
				loadSpeed: 0,
				closeSpeed: 0
			},
			onBeforeTrigger: function(e)
			{
				if ($(window).height() < 500)
				{
					e.preventDefault();
				}
			},
			onBeforeLoad: function(e)
			{
				if (typeof XenForo.LightBox == 'function')
				{
					if (XenForo._LightBoxObj === undefined)
					{
						XenForo._LightBoxObj = new XenForo.LightBox(this, containerSelector);
					}

					var $imageContainer = (parseInt(XenForo._lightBoxUniversal)
						? $('body')
						: $link.closest(containerSelector));

					console.info('Opening LightBox for %o using %s', $imageContainer, containerSelector);

					XenForo._LightBoxObj.setThumbStrip($imageContainer);
					XenForo._LightBoxObj.setImage(this.getTrigger().find('img:first'));

					$(document).triggerHandler('LightBoxOpening');
				}

				return true;
			},
			onLoad: function(e)
			{
				XenForo._LightBoxObj.setDimensions(true);
				XenForo._LightBoxObj.bindNav();

				return true;
			},
			onClose: function(e)
			{
				XenForo._LightBoxObj.setImage();
				XenForo._LightBoxObj.unbindNav();
				XenForo._LightBoxObj.resetHeight();

				return true;
			}
		});
	};

	// *********************************************************************

	XenForo.OverlayLoaderCache = {};

	/**
	 * Loads HTML and related external resources for an overlay
	 *
	 * @param jQuery Overlay trigger object
	 * @param boolean If true, cache the overlay HTML for this URL
	 * @param object Object of options for the overlay
	 */
	XenForo.OverlayLoader = function($trigger, cache, options)
	{
		this.__construct($trigger, options, cache);
	};
	XenForo.OverlayLoader.prototype =
	{
		__construct: function($trigger, options, cache)
		{
			this.$trigger = $trigger;
			this.cache = cache;
			this.options = options;
		},

		/**
		 * Initiates the loading of the overlay, or returns it from cache
		 *
		 * @param function Callback to run on successful load
		 */
		load: function(callback)
		{
			// special case for submit buttons
			if (this.$trigger.is('input:submit'))
			{
				this.cache = false;

				if (!this.xhr)
				{
					var $form = this.$trigger.closest('form'),

					serialized = $form.serializeArray();

					serialized.push(
					{
						name: this.$trigger.attr('name'),
						value: this.$trigger.attr('value')
					});

					this.xhr = XenForo.ajax(
						$form.attr('action'),
						serialized,
						$.context(this, 'loadSuccess')
					);
				}

				return;
			}

			//TODO: ability to point to extant overlay HTML, rather than loading via AJAX
			this.href = this.$trigger.data('cardurl') || this.$trigger.data('href') || this.$trigger.attr('href');

			if (!this.href)
			{
				console.warn('No overlay href found for control %o', this.$trigger);
				return false;
			}

			console.info('OverlayLoader for %s', this.href);

			this.callback = callback;

			if (this.cache && XenForo.OverlayLoaderCache[this.href])
			{
				this.createOverlay(XenForo.OverlayLoaderCache[this.href]);
			}
			else if (!this.xhr)
			{
				this.xhr = XenForo.ajax(
					this.href, '',
					$.context(this, 'loadSuccess'), { type: 'GET' }
				);
			}
		},

		/**
		 * Handles the returned ajaxdata from an overlay xhr load,
		 * Stores the template HTML then inits externals (js, css) loading
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 */
		loadSuccess: function(ajaxData, textStatus)
		{
			delete(this.xhr);

			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			// received a redirect rather than a view - follow it.
			if (ajaxData._redirectStatus && ajaxData._redirectTarget)
			{
				var fn = function()
				{
					XenForo.redirect(ajaxData._redirectTarget);
				};

				if (XenForo._manualDeferOverlay)
				{
					$(document).one('ManualDeferComplete', fn);
				}
				else
				{
					fn();
				}
				return false;
			}

			this.options.title = ajaxData.h1 || ajaxData.title;

			new XenForo.ExtLoader(ajaxData, $.context(this, 'createOverlay'));
		},

		/**
		 * Creates an overlay containing the appropriate template HTML,
		 * runs the callback specified in .load() and then shows the overlay.
		 *
		 * @param jQuery Cached $overlay object
		 */
		createOverlay: function($overlay)
		{
			var contents = ($overlay && $overlay.templateHtml) ? $overlay.templateHtml : $overlay;
			this.overlay = XenForo.createOverlay(this.$trigger, contents, this.options);

			if (this.cache)
			{
				XenForo.OverlayLoaderCache[this.href] = this.overlay.getOverlay();
			}

			if (typeof this.callback == 'function')
			{
				this.callback();
			}

			this.show();
		},

		/**
		 * Shows a finished overlay
		 */
		show: function()
		{
			if (!this.overlay)
			{
				console.warn('Attempted to call XenForo.OverlayLoader.show() for %s before overlay is created', this.href);
				this.load(this.callback);
				return;
			}

			this.overlay.load();
			$(document).trigger({
				type: 'XFOverlay',
				overlay: this.overlay,
				trigger: this.$trigger
			});
		}
	};

	// *********************************************************************

	XenForo.LoginBar = function($loginBar)
	{
		var $form = $('#login').appendTo($loginBar.find('.pageContent')),

		/**
		 * Opens the login form
		 *
		 * @param event
		 */
		openForm = function(e)
		{
			e.preventDefault();

			XenForo.chromeAutoFillFix($form);

			$form.xfSlideIn(XenForo.speed.slow, 'easeOutBack', function()
			{
				$('#LoginControl').select();

				$loginBar.expose($.extend(XenForo._overlayConfig.mask,
				{
					loadSpeed: XenForo.speed.slow,
					onBeforeLoad: function(e)
					{
						$form.css('outline', '0px solid black');
					},
					onLoad: function(e)
					{
						$form.css('outline', '');
					},
					onBeforeClose: function(e)
					{
						closeForm(false, true);
						return true;
					}
				}));
			});
		},

		/**
		 * Closes the login form
		 *
		 * @param event
		 * @param boolean
		 */
		closeForm = function(e, isMaskClosing)
		{
			if (e) e.target.blur();

			$form.xfSlideOut(XenForo.speed.fast);

			if (!isMaskClosing && $.mask)
			{
				$.mask.close();
			}
		};

		/**
		 * Toggles the login form
		 */
		$('label[for="LoginControl"]').click(function(e)
		{
			if ($(this).closest('#login').length == 0)
			{
				e.preventDefault();

				if ($form._xfSlideWrapper(true))
				{
					closeForm(e);
				}
				else
				{
					$(XenForo.getPageScrollTagName()).scrollTop(0);

					openForm(e);
				}
			}
		});

		/**
		 * Changes the text of the Log in / Sign up submit button depending on state
		 */
		$loginBar.delegate('input[name="register"]', 'click', function(e)
		{
			var $button = $form.find('input.button.primary'),
				register = $form.find('input[name="register"]:checked').val();

			$form.find('input.button.primary').val(register == '1'
				? $button.data('signupphrase')
				: $button.data('loginphrase'));
			
			$form.find('label.rememberPassword').css('visibility', (register == '1' ? 'hidden' : 'visible'));
		});

		// close form if any .click elements within it are clicked
		$loginBar.delegate('.close', 'click', closeForm);
	};

	// *********************************************************************

	XenForo.QuickSearch = function($form)
	{
		var runCount = 0;

		$('#QuickSearchPlaceholder').click(function(e) {
			e.preventDefault();
			setTimeout(function() {
				$('#QuickSearch').addClass('show');
				$('#QuickSearchPlaceholder').addClass('hide');
				$('#QuickSearchQuery').focus();
				if (XenForo.isTouchBrowser())
				{
					$('#QuickSearchQuery').blur();
				}
			}, 0);
		});

		$('#QuickSearchQuery').focus(function(focusEvent)
		{
			runCount++;
			console.log('Show quick search menu (%s)', runCount);

			if (runCount == 1 && $.browser.msie && $.browser.version < 9)
			{
				// IE 8 doesn't auto submit here...
				$form.find('input').keydown(function(e){
			        if (e.keyCode == 13) {
			            $(this).parents('form').submit();
			            return false;
			        }
			    });
			}

			if (runCount == 1)
			{
				$(XenForo._isWebkitMobile ? document.body.children : document).on('click', function(clickEvent)
				{
					if (!$(clickEvent.target).closest('#QuickSearch').length)
					{
						console.log('Hide quick search menu');

						$('#QuickSearch').removeClass('show');
						$('#QuickSearchPlaceholder').removeClass('hide');

						$form.find('.secondaryControls').slideUp(XenForo.speed.xfast, function()
						{
							$form.removeClass('active');
							if ($.browser.msie)
							{
								$('body').css('zoom', 1);
								setTimeout(function() { $('body').css('zoom', ''); }, 100);
							}
						});
					}
				});
			}

			$form.addClass('active');
			$form.find('.secondaryControls').slideDown(0);
		});
	};

	// *********************************************************************

	XenForo.configureTooltipRtl = function(config)
	{
		if (config.offset !== undefined)
		{
			config.offset = XenForo.switchOffsetRTL(config.offset);
		}

		if (config.position !== undefined)
		{
			config.position = XenForo.switchStringRTL(config.position);
		}

		return config;
	};

	/**
	 * Wrapper for jQuery Tools Tooltip
	 *
	 * @param jQuery .Tooltip
	 */
	XenForo.Tooltip = function($element)
	{
		var tipClass = String($element.data('tipclass') || ''),
			isFlipped = /(\s|^)flipped(\s|$)/.test(tipClass);

		if ($element.closest('.linkGroup').length && !isFlipped)
		{
			isFlipped = true;
			tipClass += ' flipped';
		}

		var	offsetY = parseInt($element.data('offsety'), 10) || -6,
			innerWidth = $element.is(':visible') ? $element.innerWidth() : 0,
			dataOffsetX = parseInt($element.data('offsetx'), 10) || 0,
			offsetX = dataOffsetX + innerWidth * (isFlipped ? 1 : -1),
			title = XenForo.htmlspecialchars($element.attr('title'));

		var onBeforeShow = null;

		if (innerWidth <= 0)
		{
			var positionUpdated = false;
			onBeforeShow = function()
			{
				if (positionUpdated)
				{
					return;
				}

				var width = $element.innerWidth();
				if (width <= 0)
				{
					return;
				}
				positionUpdated = true;

				offsetX = dataOffsetX + width * (isFlipped ? 1 : -1);
				$element.data('tooltip').getConf().offset = XenForo.switchOffsetRTL([ offsetY, offsetX ]);
			};
		}

		$element.attr('title', title).tooltip(XenForo.configureTooltipRtl(
		{
			delay: 0,
			position: $element.data('position') || 'top ' + (isFlipped ? 'left' : 'right'),
			offset: [ offsetY, offsetX ],
			tipClass: 'xenTooltip ' + tipClass,
			layout: '<div><span class="arrow" /></div>',
			onBeforeShow: onBeforeShow
		}));
	};

	// *********************************************************************

	XenForo.StatusTooltip = function($element)
	{
		if ($element.attr('title'))
		{
			var title = XenForo.htmlspecialchars($element.attr('title'));

			$element.attr('title', title).tooltip(XenForo.configureTooltipRtl(
			{
				effect: 'slide',
				slideOffset: 30,
				position: 'bottom right',
				offset: [ 10, 10 ],
				tipClass: 'xenTooltip statusTip',
				layout: '<div><span class="arrow" /></div>'
			}));
		}
	};

	// *********************************************************************

	XenForo.NodeDescriptionTooltip = function($title)
	{
		var description = $title.data('description');

		if (description && $(description).length)
		{
			var $description = $(description)
				.addClass('xenTooltip nodeDescriptionTip')
				.appendTo('body')
				.append('<span class="arrow" />');

			$title.tooltip(XenForo.configureTooltipRtl(
			{
				effect: 'slide',
				slideOffset: 30,
				offset: [ 30, 10 ],
				slideInSpeed: XenForo.speed.xfast,
				slideOutSpeed: 50 * XenForo._animationSpeedMultiplier,

				/*effect: 'fade',
				fadeInSpeed: XenForo.speed.xfast,
				fadeOutSpeed: XenForo.speed.xfast,*/

				predelay: 250,
				position: 'bottom right',
				tip: description,

				onBeforeShow: function()
				{
					if (!$title.data('tooltip-shown'))
					{
						if ($(window).width() < 600)
						{
							var conf = $title.data('tooltip').getConf();
							conf.slideOffset = 0;
							conf.effect = 'toggle';
							conf.offset = [20, -$title.width()];
							conf.position = ['top', 'right'];

							if (XenForo.isRTL())
							{
								conf.offset[1] *= -1;
								conf.position[1] = 'left';
							}

							$description.addClass('arrowBottom');
						}

						$title.data('tooltip-shown', true);
					}
				}
			}));
			$title.click(function() { $(this).data('tooltip').hide(); });
		}
	};

	// *********************************************************************

	XenForo.AccountMenu = function($menu)
	{
		$menu.find('.submitUnit').hide();

		$menu.find('.StatusEditor').focus(function(e)
		{
			if ($menu.is(':visible'))
			{
				$menu.find('.submitUnit').show();
			}
		});
	};

	// *********************************************************************

	XenForo.FollowLink = function($link)
	{
		$link.click(function(e)
		{
			e.preventDefault();

			$link.get(0).blur();

			XenForo.ajax(
				$link.attr('href'),
				{ _xfConfirm: 1 },
				function (ajaxData, textStatus)
				{
					if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}

					$link.xfFadeOut(XenForo.speed.fast, function()
					{
						$link
							.attr('href', ajaxData.linkUrl)
							.html(ajaxData.linkPhrase)
							.xfFadeIn(XenForo.speed.fast);
					});
				}
			);
		});
	};

	// *********************************************************************

	/**
	 * Allows relative hash links to smoothly scroll into place,
	 * Primarily used for 'x posted...' messages on bb code quote.
	 *
	 * @param jQuery a.AttributionLink
	 */
	XenForo.AttributionLink = function($link)
	{
		$link.click(function(e)
		{
			if ($(this.hash).length)
			{
				try
				{
					var hash = this.hash,
						top = $(this.hash).offset().top,
						scroller = XenForo.getPageScrollTagName();

					if ("pushState" in window.history)
					{
						window.history.pushState({}, '', window.location.toString().replace(/#.*$/, '') + hash);
					}

					$(scroller).animate({ scrollTop: top }, XenForo.speed.normal, 'easeOutBack', function()
					{
						if (!window.history.pushState)
						{
							window.location.hash = hash;
						}
					});
				}
				catch(e)
				{
					window.location.hash = this.hash;
				}

				e.preventDefault();
			}
		});
	};

	// *********************************************************************

	/**
	 * Allows clicks on one element to trigger the click event of another
	 *
	 * @param jQuery .ClickProxy[rel="{selectorForTarget}"]
	 *
	 * @return boolean false - prevents any direct action for the proxy element on click
	 */
	XenForo.ClickProxy = function($element)
	{
		$element.click(function(e)
		{
			$($element.attr('rel')).click();

			if (!$element.data('allowdefault'))
			{
				return false;
			}
		});
	};

	// *********************************************************************

	/**
	 * ReCaptcha wrapper
	 */
	XenForo.ReCaptcha = function($captcha) { this.__construct($captcha); };
	XenForo.ReCaptcha.prototype =
	{
		__construct: function($captcha)
		{
			if (XenForo.ReCaptcha.instance)
			{
				XenForo.ReCaptcha.instance.remove();
			}
			XenForo.ReCaptcha.instance = this;

			this.publicKey = $captcha.data('publickey');
			if (!this.publicKey)
			{
				return;
			}

			$captcha.siblings('noscript').remove();

			$captcha.uniqueId();
			this.$captcha = $captcha;
			this.type = 'image';

			$captcha.find('.ReCaptchaReload').click($.context(this, 'reload'));
			$captcha.find('.ReCaptchaSwitch').click($.context(this, 'switchType'));

			this.load();
			$(window).unload($.context(this, 'remove'));

			$captcha.closest('form.AutoValidator').bind(
			{
				AutoValidationDataReceived: $.context(this, 'reload')
			});
		},

		load: function()
		{
			if (window.Recaptcha)
			{
				this.create();
			}
			else
			{
				var f = $.context(this, 'create'),
					delay = ($.browser.msie && $.browser.version <= 6 ? 250 : 0); // helps IE6 loading

				$.getScript('//www.google.com/recaptcha/api/js/recaptcha_ajax.js',
					function() { setTimeout(f, delay); }
				);
			}
		},

		create: function()
		{
			var $c = this.$captcha;

			window.Recaptcha.create(this.publicKey, $c.attr('id'),
			{
				theme: 'custom',
				callback: function() {
					$c.show();
					$('#ReCaptchaLoading').remove();
					// webkit seems to overwrite this value using the back button
					$('#recaptcha_challenge_field').val(window.Recaptcha.get_challenge());
				}
			});
		},

		reload: function(e)
		{
			if (!window.Recaptcha)
			{
				return;
			}

			if (!$(e.target).is('form'))
			{
				e.preventDefault();
			}
			window.Recaptcha.reload();
		},

		switchType: function(e)
		{
			e.preventDefault();
			this.type = (this.type == 'image' ? 'audio' : 'image');
			window.Recaptcha.switch_type(this.type);
		},

		remove: function()
		{
			this.$captcha.empty().remove();
			if (window.Recaptcha)
			{
				window.Recaptcha.destroy();
			}
		}
	};
	XenForo.ReCaptcha.instance = null;

	XenForo.NoCaptcha = function($captcha) { this.__construct($captcha); };
	XenForo.NoCaptcha.prototype =
	{
		__construct: function($captcha)
		{
			this.$captcha = $captcha;
			this.noCaptchaId = null;

			$captcha.closest('form.AutoValidator').bind(
			{
				AutoValidationDataReceived: $.context(this, 'reload')
			});

			if (window.grecaptcha)
			{
				this.create();
			}
			else
			{
				XenForo.NoCaptcha._callbacks.push($.context(this, 'create'));
				$.getScript('https://www.google.com/recaptcha/api.js?onload=XFNoCaptchaCallback&render=explicit');
			}
		},

		create: function()
		{
			if (!window.grecaptcha)
			{
				return;
			}

			this.noCaptchaId = grecaptcha.render(this.$captcha[0], {sitekey: this.$captcha.data('sitekey')});
		},

		reload: function()
		{
			if (!window.grecaptcha || this.noCaptchaId === null)
			{
				return;
			}

			grecaptcha.reset(this.noCaptchaId);
		}
	};
	XenForo.NoCaptcha._callbacks = [];
	window.XFNoCaptchaCallback = function()
	{
		var cb = XenForo.NoCaptcha._callbacks;

		for (var i = 0; i < cb.length; i++)
		{
			cb[i]();
		}
	};

	// *********************************************************************

	XenForo.SolveMediaCaptcha = function($captcha) { this.__construct($captcha); };
	XenForo.SolveMediaCaptcha.prototype =
	{
		__construct: function($captcha)
		{
			if (XenForo.SolveMediaCaptcha.instance)
			{
				XenForo.SolveMediaCaptcha.instance.remove();
			}
			XenForo.SolveMediaCaptcha.instance = this;

			this.cKey = $captcha.data('c-key');
			if (!this.cKey)
			{
				return;
			}

			$captcha.siblings('noscript').remove();

			$captcha.uniqueId();
			this.$captcha = $captcha;
			this.type = 'image';

			this.load();
			$(window).unload($.context(this, 'remove'));

			$captcha.closest('form.AutoValidator').bind(
			{
				AutoValidationDataReceived: $.context(this, 'reload')
			});
		},

		load: function()
		{
			if (window.ACPuzzle)
			{
				this.create();
			}
			else
			{
				var prefix = window.location.protocol == 'https:' ? 'https://api-secure' : 'http://api';

				window.ACPuzzleOptions = {
					onload: $.context(this, 'create')
				};
				XenForo.loadJs(prefix + '.solvemedia.com/papi/challenge.ajax');
			}
		},

		create: function()
		{
			var $c = this.$captcha;

			window.ACPuzzle.create(this.cKey, $c.attr('id'), {
				theme: $c.data('theme') || 'white',
				lang: $('html').attr('lang').substr(0, 2) || 'en'
			});
		},

		reload: function(e)
		{
			if (!window.ACPuzzle)
			{
				return;
			}

			if (!$(e.target).is('form'))
			{
				e.preventDefault();
			}
			window.ACPuzzle.reload();
		},

		remove: function()
		{
			this.$captcha.empty().remove();
			if (window.ACPuzzle)
			{
				window.ACPuzzle.destroy();
			}
		}
	};
	XenForo.SolveMediaCaptcha.instance = null;

	// *********************************************************************

	XenForo.KeyCaptcha = function($captcha) { this.__construct($captcha); };
	XenForo.KeyCaptcha.prototype =
	{
		__construct: function($captcha)
		{
			this.$captcha = $captcha;

			this.$form = $captcha.closest('form');
			this.$form.uniqueId();

			this.$codeEl = this.$form.find('input[name=keycaptcha_code]');
			this.$codeEl.uniqueId();

			this.load();
			$captcha.closest('form.AutoValidator').bind({
				AutoValidationDataReceived: $.context(this, 'reload')
			});
		},

		load: function()
		{
			if (window.s_s_c_onload)
			{
				this.create();
			}
			else
			{
				var $captcha = this.$captcha;

				window.s_s_c_user_id = $captcha.data('user-id');
				window.s_s_c_session_id =  $captcha.data('session-id');
				window.s_s_c_captcha_field_id = this.$codeEl.attr('id');
				window.s_s_c_submit_button_id = 'sbutton-#-r';
				window.s_s_c_web_server_sign =  $captcha.data('sign');
				window.s_s_c_web_server_sign2 =  $captcha.data('sign2');
				document.s_s_c_element = this.$form[0];
				document.s_s_c_debugmode = 1;

				var $div = $('#div_for_keycaptcha');
				if (!$div.length)
				{
					$('body').append('<div id="div_for_keycaptcha" />');
				}

				XenForo.loadJs('https://backs.keycaptcha.com/swfs/cap.js');
			}
		},

		create: function()
		{
			window.s_s_c_onload(this.$form.attr('id'), this.$codeEl.attr('id'), 'sbutton-#-r');
		},

		reload: function(e)
		{
			if (!window.s_s_c_onload)
			{
				return;
			}

			if (!$(e.target).is('form'))
			{
				e.preventDefault();
			}
			this.load();
		}
	};

	// *********************************************************************

	/**
	 * Loads a new (non-ReCaptcha) CAPTCHA upon verification failure
	 *
	 * @param jQuery #Captcha
	 */
	XenForo.Captcha = function($container)
	{
		var $form = $container.closest('form');

		$form.off('AutoValidationDataReceived.captcha').on('AutoValidationDataReceived.captcha', function(e)
		{
			$container.fadeTo(XenForo.speed.fast, 0.5);

			XenForo.ajax($container.data('source'), {}, function(ajaxData, textStatus)
			{
				if (XenForo.hasResponseError(ajaxData))
				{
					return false;
				}

				if (XenForo.hasTemplateHtml(ajaxData))
				{
					$container.xfFadeOut(XenForo.speed.xfast, function()
					{
						$(ajaxData.templateHtml).xfInsert('replaceAll', $container, 'xfFadeIn', XenForo.speed.xfast);
					});
				}
			});
		});
	};

	// *********************************************************************

	/**
	 * Handles resizing of BB code [img] tags that would overflow the page
	 *
	 * @param jQuery img.bbCodeImage
	 */
	XenForo.BbCodeImage = function($image) { this.__construct($image); };
	XenForo.BbCodeImage.prototype =
	{
		__construct: function($image)
		{
			this.$image = $image;
			this.actualWidth = 0;

			if ($image.closest('a').length)
			{
				return;
			}

			$image
				.attr('title', XenForo.phrases.click_image_show_full_size_version || 'Show full size')
				.click($.context(this, 'toggleFullSize'));

			if (!XenForo.isTouchBrowser())
			{
				this.$image.tooltip(XenForo.configureTooltipRtl({
					effect: 'slide',
					slideOffset: 30,
					position: 'top center',
					offset: [ 45, 0 ],
					tipClass: 'xenTooltip bbCodeImageTip',
					onBeforeShow: $.context(this, 'isResized'),
					onShow: $.context(this, 'addTipClick')
				}));
			}

			if (!this.getImageWidth())
			{
				var src = $image.attr('src');

				$image.bind({
					load: $.context(this, 'getImageWidth')
				});
				//$image.attr('src', 'about:blank');
				$image.attr('src', src);
			}
		},

		/**
		 * Attempts to store the un-resized width of the image
		 *
		 * @return integer
		 */
		getImageWidth: function()
		{
			this.$image.css({'max-width': 'none', 'max-height': 'none'});
			this.actualWidth = this.$image.width();
			this.$image.css({'max-width': '', 'max-height': ''});

			//console.log('BB Code Image %o has width %s', this.$image, this.actualWidth);

			return this.actualWidth;
		},

		/**
		 * Shows and hides a full-size version of the image
		 *
		 * @param event
		 */
		toggleFullSize: function(e)
		{
			if (this.actualWidth == 0)
			{
				this.getImageWidth();
			}
			
			var currentWidth = this.$image.width(),
				offset, cssOffset, scale,
				scrollLeft, scrollTop,
				layerX, layerY,
				$fullSizeImage,
				speed = window.navigator.userAgent.match(/Android|iOS|iPhone|iPad|Mobile Safari/i) ? 0 : XenForo.speed.normal,
				easing = 'easeInOutQuart';

			if (this.actualWidth > currentWidth)
			{
				offset = this.$image.offset();
				cssOffset = offset;
				scale = this.actualWidth / currentWidth;
				layerX = e.pageX - offset.left;
				layerY = e.pageY - offset.top;

				if (XenForo.isRTL())
				{
					cssOffset.right = $('html').width() - cssOffset.left - currentWidth;
					cssOffset.left = 'auto';
				}

				$fullSizeImage = $('<img />', { src: this.$image.attr('src') })
					.addClass('bbCodeImageFullSize')
					.css('width', currentWidth)
					.css(cssOffset)
					.click(function()
					{
						$(this).remove();
						$(XenForo.getPageScrollTagName()).scrollLeft(0).scrollTop(offset.top);
					})
					.appendTo('body')
					.animate({ width: this.actualWidth }, speed, easing);

				// remove full size image if an overlay is about to open
				$(document).one('OverlayOpening', function()
				{
					$fullSizeImage.remove();
				});
				
				// remove full-size image if the source image is contained by a ToggleTrigger target that is closing 
				$(document).bind('ToggleTriggerEvent', $.context(function(e, args)
				{				
					if (args.closing && args.$target.find(this.$image).length)
					{
						console.info('Target is parent of this image %o', this.$image);
						$fullSizeImage.remove();
					}
				}, this));

				if (e.target == this.$image.get(0))
				{
					scrollLeft = offset.left + (e.pageX - offset.left) * scale - $(window).width() / 2;
					scrollTop = offset.top + (e.pageY - offset.top) * scale - $(window).height() / 2;
				}
				else
				{
					scrollLeft = offset.left + (this.actualWidth / 2) - $(window).width() / 2;
					scrollTop = offset.top + (this.$image.height() * scale / 2) - $(window).height() / 2;
				}

				$(XenForo.getPageScrollTagName()).animate(
				{
					scrollLeft: scrollLeft,
					scrollTop: scrollTop
				}, speed, easing, $.context(function()
				{
					var tooltip = this.$image.data('tooltip');
					if (tooltip)
					{
						tooltip.hide();
					}
				}, this));
			}
			else
			{
				console.log('BBCodeImage: this.actualWidth = %d, currentWidth = %d', this.actualWidth, currentWidth);
			}
		},

		isResized: function(e)
		{
			var width = this.$image.width();

			if (!width)
			{
				return false;
			}

			if (this.getImageWidth() <= width)
			{
				//console.log('Image is not resized %o', this.$image);
				return false;
			}
		},

		addTipClick: function(e)
		{
			if (!this.tipClickAdded)
			{
				$(this.$image.data('tooltip').getTip()).click($.context(this, 'toggleFullSize'));
				this.tipClickAdded = true;
			}
		}
	};

	// *********************************************************************

	/**
	 * Wrapper for the jQuery Tools Tabs system
	 *
	 * @param jQuery .Tabs
	 */
	XenForo.Tabs = function($tabContainer) { this.__construct($tabContainer); };
	XenForo.Tabs.prototype =
	{
		__construct: function($tabContainer)
		{
			// var useHistory = XenForo.isPositive($tabContainer.data('history'));
			// TODO: disabled until base tag issues are resolved
			var useHistory = false;

			this.$tabContainer = $tabContainer;
			this.$panes = $($tabContainer.data('panes'));

			/*if (useHistory)
			{
				$tabContainer.find('a[href]').each(function()
				{
					var $this = $(this), hrefParts = $this.attr('href').split('#');
					if (hrefParts[1] && location.pathname == hrefParts[0])
					{
						$this.attr('href', '#' + hrefParts[1]);
					}
				});
			}*/

			var $tabs = $tabContainer.find('a');
			if (!$tabs.length)
			{
				$tabs = $tabContainer.children();
			}

			var $active = $tabs.filter('.active'),
				initialIndex = 0;

			if ($active.length)
			{
				$tabs.each(function() {
					if (this == $active.get(0))
					{
						return false;
					}

					initialIndex++;
				});
			}

			if (window.location.hash.length > 1)
			{
				var id = window.location.hash.substr(1),
					matchIndex = -1,
					matched = false;

				this.$panes.each(function() {
					matchIndex++;
					if ($(this).attr('id') === id)
					{
						matched = true;
						return false;
					}
					return true;
				});
				if (matched)
				{
					initialIndex = matchIndex;
				}
			}

			$tabContainer.tabs(this.$panes, {
				current: 'active',
				history: useHistory,
				initialIndex: initialIndex,
				onBeforeClick: $.context(this, 'onBeforeClick')
			});
			this.api = $tabContainer.data('tabs');
		},

		getCurrentTab: function()
		{
			return this.api.getIndex();
		},

		click: function(index)
		{
			this.api.click(index);
		},

		onBeforeClick: function(e, index)
		{
			this.$tabContainer.children().each(function(i)
			{
				if (index == i)
				{
					$(this).addClass('active');
				}
				else
				{
					$(this).removeClass('active');
				}
			});

			var $pane = $(this.$panes.get(index)),
				loadUrl = $pane.data('loadurl');

			if (loadUrl)
			{
				$pane.data('loadurl', '');

				XenForo.ajax(loadUrl, {}, function(ajaxData)
				{
					if (XenForo.hasTemplateHtml(ajaxData) || XenForo.hasTemplateHtml(ajaxData, 'message'))
					{
						new XenForo.ExtLoader(ajaxData, function(ajaxData)
						{
							var $html;

							if (ajaxData.templateHtml)
							{
								$html = $(ajaxData.templateHtml);
							}
							else if (ajaxData.message)
							{
								$html = $('<div class="section" />').html(ajaxData.message);
							}

							$pane.html('');
							if ($html)
							{
								$html.xfInsert('appendTo', $pane, 'xfFadeIn', 0);
							}
						});
					}
					else if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}
				}, {type: 'GET'});
			}
		}
	};

	// *********************************************************************

	/**
	 * Handles a like / unlike link being clicked
	 *
	 * @param jQuery a.LikeLink
	 */
	XenForo.LikeLink = function($link)
	{
		$link.click(function(e)
		{
			e.preventDefault();

			var $link = $(this);

			XenForo.ajax(this.href, {}, function(ajaxData, textStatus)
			{
				if (XenForo.hasResponseError(ajaxData))
				{
					return false;
				}

				$link.stop(true, true);

				if (ajaxData.term) // term = Like / Unlike
				{
					$link.find('.LikeLabel').html(ajaxData.term);

					if (ajaxData.cssClasses)
					{
						$.each(ajaxData.cssClasses, function(className, action)
						{
							$link[action == '+' ? 'addClass' : 'removeClass'](className);
						});
					}
				}

				if (ajaxData.templateHtml === '')
				{
					$($link.data('container')).xfFadeUp(XenForo.speed.fast, function()
					{
						$(this).empty().xfFadeDown(0);
					});
				}
				else
				{
					var $container    = $($link.data('container')),
						$likeText     = $container.find('.LikeText'),
						$templateHtml = $(ajaxData.templateHtml);

					if ($likeText.length)
					{
						// we already have the likes_summary template in place, so just replace the text
						$likeText.xfFadeOut(50, function()
						{
							var textContainer = this.parentNode;

							$(this).remove();

							$templateHtml.find('.LikeText').xfInsert('appendTo', textContainer, 'xfFadeIn', 50);
						});
					}
					else
					{
						new XenForo.ExtLoader(ajaxData, function()
						{
							$templateHtml.xfInsert('appendTo', $container);
						});
					}
				}
			});
		});
	};

	// *********************************************************************

	XenForo.Facebook =
	{
		initialized: false,
		loading: false,
		appId: '',
		fbUid: 0,
		authResponse: {},
		locale: 'en-US',

		init: function()
		{
			if (XenForo.Facebook.initialized)
			{
				return;
			}
			XenForo.Facebook.initialized = true;
			XenForo.Facebook.loading = false;

			$(document.body).append($('<div id="fb-root" />'));

			var fbInfo = {
				version: 'v2.4',
				xfbml: true,
				oauth: true,
				channelUrl: XenForo.canonicalizeUrl('fb_channel.php?l=' + XenForo.Facebook.locale)
			};
			if (XenForo.Facebook.appId)
			{
				fbInfo.appId = XenForo.Facebook.appId;
			}

			FB.init(fbInfo);
			if (XenForo.Facebook.appId && XenForo.Facebook.fbUid)
			{
				FB.Event.subscribe('auth.authResponseChange', XenForo.Facebook.sessionChange);
				FB.getLoginStatus(XenForo.Facebook.sessionChange);

				if (XenForo.visitor.user_id)
				{
					$(document).delegate('a.LogOut:not(.OverlayTrigger)', 'click', XenForo.Facebook.eLogOutClick);
				}
			}
		},

		start: function()
		{
			var cookieUid = $.getCookie('fbUid');
			if (cookieUid && cookieUid.length)
			{
				XenForo.Facebook.fbUid = parseInt(cookieUid, 10);
			}

			if ($('.fb-post, .fb-video').length)
			{
				XenForo.Facebook.forceInit = true;
			}

			if (!XenForo.Facebook.forceInit && (!XenForo.Facebook.appId || !XenForo.Facebook.fbUid))
			{
				return;
			}

			XenForo.Facebook.load();
		},

		load: function()
		{
			if (XenForo.Facebook.initialized)
			{
				FB.XFBML.parse();
				return;
			}

			if (XenForo.Facebook.loading)
			{
				return;
			}
			XenForo.Facebook.loading = true;

			XenForo.Facebook.locale = $('html').attr('lang').replace('-', '_');
			if (!XenForo.Facebook.locale)
			{
				XenForo.Facebook.locale = 'en_US';
			}

			var e = document.createElement('script'),
				locale = XenForo.Facebook.locale.replace('-', '_');
			e.src = '//connect.facebook.net/' + XenForo.Facebook.locale + '/sdk.js';
			e.async = true;

			window.fbAsyncInit = XenForo.Facebook.init;
			document.getElementsByTagName('head')[0].appendChild(e);
		},

		sessionChange: function(response)
		{
			if (!XenForo.Facebook.fbUid)
			{
				return;
			}

			var authResponse = response.authResponse, visitor = XenForo.visitor;
			XenForo.Facebook.authResponse = authResponse;

			if (authResponse && !visitor.user_id)
			{
				if (!XenForo._noSocialLogin)
				{
					// facebook user, connect!
					XenForo.alert(XenForo.phrases.logging_in + '...', '', 8000);
					setTimeout(function() {
						XenForo.redirect(
							'index.php?register/facebook&t=' + escape(authResponse.accessToken)
							+ '&redirect=' + escape(window.location)
						);
					}, 250);
				}
			}
		},

		logout: function(fbData, returnPage)
		{
			var location = $('a.LogOut:not(.OverlayTrigger)').attr('href');
			if (!location)
			{
				location = 'index.php?logout/&_xfToken=' + XenForo._csrfToken;
			}
			if (returnPage)
			{
				location += (location.indexOf('?') >= 0 ? '&' : '?') + 'redirect=' + escape(window.location);
			}
			XenForo.redirect(location);
		},

		eLogOutClick: function(e)
		{
			if (XenForo.Facebook.authResponse && XenForo.Facebook.authResponse.userID)
			{
				FB.logout(XenForo.Facebook.logout);
				return false;
			}
		}
	};

	// *********************************************************************
	/**
	 * Turns an :input into a Prompt
	 *
	 * @param {Object} :input[placeholder]
	 */
	XenForo.Prompt = function($input)
	{
		this.__construct($input);
	};
	if ('placeholder' in document.createElement('input'))
	{
		// native placeholder support
		XenForo.Prompt.prototype =
		{
			__construct: function($input)
			{
				this.$input = $input;
			},

			isEmpty: function()
			{
				return (this.$input.strval() === '');
			},

			val: function(value, focus)
			{
				if (value === undefined)
				{
					return this.$input.val();
				}
				else
				{
					if (focus)
					{
						this.$input.focus();
					}

					return this.$input.val(value);
				}
			}
		};
	}
	else
	{
		// emulate placeholder support
		XenForo.Prompt.prototype =
		{
			__construct: function($input)
			{
				console.log('Emulating placeholder behaviour for %o', $input);

				this.placeholder = $input.attr('placeholder');

				this.$input = $input.bind(
				{
					focus: $.context(this, 'setValueMode'),
					blur:  $.context(this, 'setPromptMode')
				});

				this.$input.closest('form').bind(
				{
					submit: $.context(this, 'eFormSubmit'),
					AutoValidationBeforeSubmit: $.context(this, 'eFormSubmit'),
					AutoValidationComplete: $.context(this, 'eFormSubmitted')
				});

				this.setPromptMode();
			},

			/**
			 * If the prompt box contains no text, or contains the prompt text (only) it is 'empty'
			 *
			 * @return boolean
			 */
			isEmpty: function()
			{
				var val = this.$input.val();

				return (val === '' || val == this.placeholder);
			},

			/**
			 * When exiting the prompt box, update its contents if necessary
			 */
			setPromptMode: function()
			{
				if (this.isEmpty())
				{
					this.$input.val(this.placeholder).addClass('prompt');
				}
			},

			/**
			 * When entering the prompt box, clear its contents if it is 'empty'
			 */
			setValueMode: function()
			{
				if (this.isEmpty())
				{
					this.$input.val('').removeClass('prompt').select();
				}
			},

			/**
			 * Gets or sets the value of the prompt and puts it into the correct mode for its contents
			 *
			 * @param string value
			 */
			val: function(value, focus)
			{
				// get value
				if (value === undefined)
				{
					if (this.isEmpty())
					{
						return '';
					}
					else
					{
						return this.$input.val();
					}
				}

				// clear value
				else if (value === '')
				{
					this.$input.val('');

					if (focus === undefined)
					{
						this.setPromptMode();
					}
				}

				// set value
				else
				{
					this.setValueMode();
					this.$input.val(value);
				}
			},

			/**
			 * When the form is submitted, empty the prompt box if it is 'empty'
			 *
			 * @return boolean true;
			 */
			eFormSubmit: function()
			{
				if (this.isEmpty())
				{
					this.$input.val('');
				}

				return true;
			},

			/**
			 * Fires immediately after the form has sent its AJAX submission
			 */
			eFormSubmitted: function()
			{
				this.setPromptMode();
			}
		};
	};

	// *********************************************************************

	/**
	 * Turn in input:text.SpinBox into a Spin Box
	 * Requires a parameter class of 'SpinBox' and an attribute of 'data-step' with a numeric step value.
	 * data-max and data-min parameters are optional.
	 *
	 * @param {Object} $input
	 */
	XenForo.SpinBox = function($input) { this.__construct($input); };
	XenForo.SpinBox.prototype =
	{
		__construct: function($input)
		{
			var param,
				inputWidth,
				$plusButton,
				$minusButton;

			if ($input.attr('step') === undefined)
			{
				console.warn('ERROR: No data-step attribute specified for spinbox.');
				return;
			}

			this.parameters = { step: null, min:  null, max:  null };

			for (param in this.parameters)
			{
				if ($input.attr(param) === undefined)
				{
					delete this.parameters[param];
				}
				else
				{
					this.parameters[param] = parseFloat($input.attr(param));
				}
			}

			inputWidth = $input.width();

			$plusButton  = $('<input type="button" class="button spinBoxButton up" value="+" data-plusminus="+" tabindex="-1" />')
				.insertAfter($input)
				.focus($.context(this, 'eFocusButton'))
				.click($.context(this, 'eClickButton'))
				.mouseenter($.context(this, 'eMouseEnter'))
				.mousedown($.context(this, 'eMousedownButton'))
				.on('mouseleave mouseup', $.context(this, 'eMouseupButton'));
			$minusButton = $('<input type="button" class="button spinBoxButton down" value="-" data-plusminus="-" tabindex="-1" />')
				.insertAfter($plusButton)
				.focus($.context(this, 'eFocusButton'))
				.click($.context(this, 'eClickButton'))
				.mouseenter($.context(this, 'eMouseEnter'))
				.mousedown($.context(this, 'eMousedownButton'))
				.on('mouseleave mouseup', $.context(this, 'eMouseupButton'));

			// set up the input
			this.$input = $input
				.attr('autocomplete', 'off')
				.blur($.context(this, 'eBlurInput'))
				.keyup($.context(this, 'eKeyupInput'));

			// force validation to occur on form submit
			this.$input.closest('form').bind('submit', $.context(this, 'eBlurInput'));

			// initial constraint
			this.$input.val(this.constrain(this.getValue()));

			this.mouseTarget = null;
		},

		/**
		 * Returns the (numeric) value of the spinbox
		 *
		 * @return float
		 */
		getValue: function()
		{
			var value = parseFloat(this.$input.val());

			value = (isNaN(value)) ? parseFloat(this.$input.val().replace(/[^0-9.]/g, '')) : value;

			return (isNaN(value) ? 0 : value);
		},

		/**
		 * Asserts that the value of the spinbox is within defined min and max parameters.
		 *
		 * @param float Spinbox value
		 *
		 * @return float
		 */
		constrain: function(value)
		{
			if (this.parameters.min !== undefined && value < this.parameters.min)
			{
				console.warn('Minimum value for SpinBox = %s\n %o', this.parameters.min, this.$input);
				return this.parameters.min;
			}
			else if (this.parameters.max !== undefined && value > this.parameters.max)
			{
				console.warn('Maximum value for SpinBox = %s\n %o', this.parameters.max, this.$input);
				return this.parameters.max;
			}
			else
			{
				return value;
			}
		},

		/**
		 * Takes the value of the SpinBox input to the nearest step.
		 *
		 * @param string +/- Take the value up or down
		 */
		stepValue: function(plusMinus)
		{
			if (this.$input.prop('readonly'))
			{
				return false;
			}

			var val = this.getValue(),
				mod = val % this.parameters.step,
				posStep = (plusMinus == '+'),
				newVal = val - mod;

			if (!mod || (posStep && mod > 0) || (!posStep && mod < 0))
			{
				newVal = newVal + this.parameters.step * (posStep ? 1 : -1);
			}

			this.$input.val(this.constrain(newVal));
			this.$input.triggerHandler('change');
		},

		/**
		 * Handles the input being blurred. Removes the 'pseudofocus' class and constrains the spinbox value.
		 *
		 * @param Event e
		 */
		eBlurInput: function(e)
		{
			this.$input.val(this.constrain(this.getValue()));
		},

		/**
		 * Handles key events on the spinbox input. Up and down arrows perform a value step.
		 *
		 * @param Event e
		 *
		 * @return false|undefined
		 */
		eKeyupInput: function(e)
		{
			switch (e.which)
			{
				case 38: // up
				{
					this.stepValue('+');
					this.$input.select();
					return false;
				}

				case 40: // down
				{
					this.stepValue('-');
					this.$input.select();
					return false;
				}
			}
		},

		/**
		 * Handles focus events on spinbox buttons.
		 *
		 * Does not allow buttons to keep focus, returns focus to the input.
		 *
		 * @param Event e
		 *
		 * @return boolean false
		 */
		eFocusButton: function(e)
		{
			return false;
		},

		/**
		 * Handles click events on spinbox buttons.
		 *
		 * The buttons are assumed to have data-plusMinus attributes of + or -
		 *
		 * @param Event e
		 */
		eClickButton: function(e)
		{
			this.stepValue($(e.target).data('plusminus'));
			this.$input.focus();
			this.$input.select();
		},

		/**
		 * Handles a mouse-down event on a spinbox button in order to allow rapid repeats.
		 *
		 * @param Event e
		 */
		eMousedownButton: function(e)
		{
			this.eMouseupButton(e); // don't orphan
			this.mouseTarget = e.target;

			this.holdTimeout = setTimeout(
				$.context(function()
				{
					this.holdInterval = setInterval($.context(function() { this.stepValue(e.target.value); }, this), 75);
				}, this
			), 500);
		},

		/**
		 * Handles re-entering while holding the mouse.
		 *
		 * @param e
		 */
		eMouseEnter: function(e)
		{
			if (e.which && e.target == this.mouseTarget)
			{
				this.holdInterval = setInterval($.context(function() { this.stepValue(e.target.value); }, this), 75);
			}
		},

		/**
		 * Handles a mouse-up event on a spinbox button in order to halt rapid repeats.
		 *
		 * @param Event e
		 */
		eMouseupButton: function(e)
		{
			clearTimeout(this.holdTimeout);
			clearInterval(this.holdInterval);
			if (e.type == 'mouseup')
			{
				this.mouseTarget = null;
			}
		}
	};

	// *********************************************************************

	/**
	 * Allows an input:checkbox or input:radio to disable subsidiary controls
	 * based on its own state
	 *
	 * @param {Object} $input
	 */
	XenForo.Disabler = function($input)
	{
		/**
		 * Sets the disabled state of form elements being controlled by this disabler.
		 *
		 * @param Event e
		 * @param boolean If true, this is the initialization call
		 */
		var setStatus = function(e, init)
		{
			//console.info('Disabler %o for child container: %o', $input, $childContainer);

			var $childControls = $childContainer.find('input, select, textarea, button, .inputWrapper, .taggingInput'),
				speed = init ? 0 : XenForo.speed.fast,
				select = function(e)
				{
					$childContainer.find('input:not([type=hidden], [type=file]), textarea, select, button').first().focus().select();
				};

			if ($input.is(':checked:enabled'))
			{
				$childContainer
					.removeAttr('disabled')
					.removeClass('disabled')
					.trigger('DisablerDisabled');

				$childControls
					.removeAttr('disabled')
					.removeClass('disabled');

				if ($input.hasClass('Hider'))
				{
					if (init)
					{
						$childContainer.show();
					}
					else
					{
						$childContainer.xfFadeDown(speed, init ? null : select);
					}
				}
				else if (!init)
				{
					select.call();
				}
			}
			else
			{
				if ($input.hasClass('Hider'))
				{
					if (init)
					{
						$childContainer.hide();
					}
					else
					{
						$childContainer.xfFadeUp(speed, null, speed, 'easeInBack');
					}
				}

				$childContainer
					.prop('disabled', true)
					.addClass('disabled')
					.trigger('DisablerEnabled');

				$childControls
					.prop('disabled', true)
					.addClass('disabled')
					.each(function(i, ctrl)
					{
						var $ctrl = $(ctrl),
							disabledVal = $ctrl.data('disabled');

						if (disabledVal !== null && typeof(disabledVal) != 'undefined')
						{
							$ctrl.val(disabledVal);
						}
					});
			}
		},

		$childContainer = $('#' + $input.attr('id') + '_Disabler'),

		$form = $input.closest('form');

		var setStatusDelayed = function()
		{
			setTimeout(setStatus, 0);
		};

		if ($input.is(':radio'))
		{
			$form.find('input:radio[name="' + $input.fieldName() + '"]').click(setStatusDelayed);
		}
		else
		{
			$input.click(setStatusDelayed);
		}

		$form.bind('reset', setStatusDelayed);
		$form.bind('XFRecalculate', function() { setStatus(null, true); });

		setStatus(null, true);

		$childContainer.find('label, input, select, textarea').click(function(e)
		{
			if (!$input.is(':checked'))
			{
				$input.prop('checked', true);
				setStatus();
			}
		});

		this.setStatus = setStatus;
	};

	// *********************************************************************

	/**
	 * Quick way to check or toggle all specified items. Works in one of two ways:
	 * 1) If the control is a checkbox, a data-target attribute specified a jQuery
	 * 	selector for a container within which all checkboxes will be toggled
	 * 2) If the control is something else, the data-target attribute specifies a
	 * 	jQuery selector for the elements themselves that will be selected.
	 *
	 *  @param jQuery .CheckAll
	 */
	XenForo.CheckAll = function($control)
	{
		if ($control.is(':checkbox'))
		{
			var $target = $control.data('target') ? $($control.data('target')) : false;
			if (!$target || !$target.length)
			{
				$target = $control.closest('form');
			}

			var getCheckBoxes = function()
			{
				var $checkboxes,
					filter = $control.data('filter');

				$checkboxes = filter
					? $target.find(filter).filter('input:checkbox')
					: $target.find('input:checkbox');

				return $checkboxes;
			};

			var setSelectAllState = function()
			{
				var $checkboxes = getCheckBoxes(),
					allSelected = $checkboxes.length > 0;

				$checkboxes.each(function() {
					if ($(this).is($control))
					{
						return true;
					}

					if (!$(this).prop('checked'))
					{
						allSelected = false;
						return false;
					}
				});

				$control.prop('checked', allSelected);
			};
			setSelectAllState();

			var toggleAllRunning = false;

			$target.on('click', 'input:checkbox', function(e)
			{
				if (toggleAllRunning)
				{
					return;
				}

				var $target = $(e.target);
				if ($target.is($control))
				{
					return;
				}

				if ($control.data('filter'))
				{
					if (!$target.closest($control.data('filter')).length)
					{
						return;
					}
				}

				setSelectAllState();
			});

			$control.click(function(e)
			{
				if (toggleAllRunning)
				{
					return;
				}

				toggleAllRunning = true;
				getCheckBoxes().prop('checked', e.target.checked).triggerHandler('click');
				toggleAllRunning = false;
			});

			$control.on('XFRecalculate', setSelectAllState);
		}
		else
		{
			$control.click(function(e)
			{
				var target = $control.data('target');

				if (target)
				{
					$(target).prop('checked', true);
				}
			});
		}
	};

	// *********************************************************************

	/**
	 * Method to allow an input (usually a checkbox) to alter the selection of others.
	 * When checking the target checkbox, it will also check any controls matching data-check
	 * and un-check any controls matching data-uncheck
	 *
	 * @param jQuery input.AutoChecker[data-check, data-uncheck]
	 */
	XenForo.AutoChecker = function($control)
	{
		$control.click(function(e)
		{
			if (this.checked)
			{
				var selector = null;

				$.each({ check: true, uncheck: false }, function(dataField, checkState)
				{
					if (selector = $control.data(dataField))
					{
						$(selector).each(function()
						{
							this.checked = checkState;

							var Disabler = $(this).data('XenForo.Disabler');

							if (typeof Disabler == 'object')
							{
								Disabler.setStatus();
							}
						});
					}
				});
			}
		});
	};

	// *********************************************************************

	/**
	 * Converts a checkbox/radio plus label into a toggle button.
	 *
	 * @param jQuery label.ToggleButton
	 */
	XenForo.ToggleButton = function($label)
	{
		var $button,

		setCheckedClasses = function()
		{
			$button[($input.is(':checked') ? 'addClass' : 'removeClass')]('checked');
		},

		$input = $label.hide().find('input:checkbox, input:radio').first(),

		$list = $label.closest('ul, ol').bind('toggleButtonClick', setCheckedClasses);

		if (!$input.length && $label.attr('for'))
		{
			$input = $('#' + $label.attr('for'));
		}

		$button = $('<a />')
			.text($label.attr('title') || $label.text())
			.insertBefore($label)
			.attr(
			{
				'class': 'button ' + $label.attr('class'),
				'title': $label.text()
			})
			.click(function(e)
			{
				$input.click();

				if ($list.length)
				{
					$list.triggerHandler('toggleButtonClick');
				}
				else
				{
					setCheckedClasses();
				}

				return false;
			});

		$label.closest('form').bind('reset', function(e)
		{
			setTimeout(setCheckedClasses, 100);
		});

		setCheckedClasses();
	};

	// *********************************************************************

	/**
	 * Allows files to be uploaded in-place without a page refresh
	 *
	 * @param jQuery form.AutoInlineUploader
	 */
	XenForo.AutoInlineUploader = function($form)
	{
		/**
		 * Fires when the contents of an input:file change.
		 * Submits the form into a temporary iframe.
		 *
		 * @param event e
		 */
		var $uploader = $form.find('input:file').each(function()
		{
			var $target = $(this).change(function(e)
			{
				if ($(e.target).val() != '')
				{
					var $iframe,
						$hiddenInput;

					$iframe = $('<iframe src="about:blank" style="display:none; background-color: white" name="AutoInlineUploader"></iframe>')
						.insertAfter($(e.target))
						.load(function(e)
						{
							var $iframe = $(e.target),
								ajaxData = $iframe.contents().text(),
								eComplete = null;

							// Opera fires this function when it's not done with no data
							if (!ajaxData)
							{
								return false;
							}

							// alert the global progress indicator that the transfer is complete
							$(document).trigger('PseudoAjaxStop');

							$uploader = $uploaderOrig.clone(true).replaceAll($target);

							// removing the iframe after a delay to prevent Firefox' progress indicator staying active
							setTimeout(function() { $iframe.remove(); }, 500);

							try
							{
								ajaxData = $.parseJSON(ajaxData);
								console.info('Inline file upload completed successfully. Data: %o', ajaxData);
							}
							catch(e)
							{
								console.error(ajaxData);
								return false;
							}

							if (XenForo.hasResponseError(ajaxData))
							{
								return false;
							}

							$('input:submit', this.$form).removeAttr('disabled');

							eComplete = new $.Event('AutoInlineUploadComplete');
							eComplete.$form = $form;
							eComplete.ajaxData = ajaxData;

							$form.trigger(eComplete);

							console.log(ajaxData);

							if (!eComplete.isDefaultPrevented() && ajaxData.message)
							{
								XenForo.alert(ajaxData.message, '', 2500);
							}
						});

					$hiddenInput = $('<span>'
						+ '<input type="hidden" name="_xfNoRedirect" value="1" />'
						+ '<input type="hidden" name="_xfResponseType" value="json-text" />'
						+ '<input type="hidden" name="_xfUploader" value="1" />'
						+ '</span>')
						.appendTo($form);

					$form.attr('target', 'AutoInlineUploader')
						.submit()
						.trigger('AutoInlineUploadStart');

					$hiddenInput.remove();

					// fire the event that will be caught by the global progress indicator
					$(document).trigger('PseudoAjaxStart');

					$form.find('input:submit').prop('disabled', true);
				}
			}),

			$uploaderOrig = $target.clone(true);
		});
	};

	// *********************************************************************

	XenForo.MultiSubmitFix = function($form)
	{
		var selector = 'input:submit, input:reset, input.PreviewButton, input.DisableOnSubmit',
			enable = function()
			{
				$(window).unbind('unload', enable);

				$form.trigger('EnableSubmitButtons').find(selector)
					.removeClass('disabled')
					.removeAttr('disabled');
			};

		var disable = function(e)
		{
			setTimeout(function()
			{
				/**
				 * Workaround for a Firefox issue that prevents resubmission after back button,
				 * however the workaround triggers a webkit rendering bug.
				 */
				if (!$.browser.webkit)
				{
					$(window).bind('unload', enable);
				}

				$form.trigger('DisableSubmitButtons').find(selector)
					.prop('disabled', true)
					.addClass('disabled');
			}, 0);

			setTimeout(enable, 5000);
		};

		$form.data('MultiSubmitEnable', enable)
			.data('MultiSubmitDisable', disable)
			.submit(disable);

		return enable;
	};

	// *********************************************************************

	/**
	 * Handler for radio/checkbox controls that cause the form to submit when they are altered
	 *
	 * @param jQuery input:radio.SubmitOnChange, input:checkbox.SubmitOnChange, label.SubmitOnChange
	 */
	XenForo.SubmitOnChange = function($input)
	{
		if ($input.is('label'))
		{
			$input = $input.find('input:radio, input:checkbox');
			if (!$input.length)
			{
				return;
			}
		}

		$input.click(function(e)
		{
			clearTimeout(e.target.form.submitTimeout);

			e.target.form.submitTimeout = setTimeout(function()
			{
				$(e.target).closest('form').submit();
			}, 500);
		});
	};

	// *********************************************************************

	/**
	 * Handler for automatic AJAX form validation and error management
	 *
	 * Forms to be auto-validated require the following attributes:
	 *
	 * * data-fieldValidatorUrl: URL of a JSON-returning validator for a single field, using _POST keys of 'name' and 'value'
	 * * data-optInOut: (Optional - default = OptOut) Either OptIn or OptOut, depending on the validation mode. Fields with a class of OptIn are included in opt-in mode, while those with OptOut are excluded in opt-out mode.
	 * * data-exitUrl: (Optional - no default) If defined, any form reset event will redirect to this URL.
	 * * data-existingDataKey: (Optional) Specifies the primary key of the data being manipulated. If this is not present, a hidden input with class="ExistingDataKey" is searched for.
	 * * data-redirect: (Optional) If set, the browser will redirect to the returned _redirectTarget from the ajaxData response after validation
	 *
	 * @param jQuery form.AutoValidator
	 */
	XenForo.AutoValidator = function($form) { this.__construct($form); };
	XenForo.AutoValidator.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form.bind(
			{
				submit: $.context(this, 'ajaxSave'),
				reset:  $.context(this, 'formReset'),
				BbCodeWysiwygEditorAutoSave: $.context(this, 'editorAutoSave')
			});

			this.$form.find('input[type="submit"]').click($.context(this, 'setClickedSubmit'));

			this.fieldValidatorUrl = this.$form.data('fieldvalidatorurl');
			this.optInMode = this.$form.data('optinout') || 'OptOut';
			this.ajaxSubmit = (XenForo.isPositive(this.$form.data('normalsubmit')) ? false : true);
			this.submitPending = false;

			this.fieldValidationTimeouts = {};
			this.fieldValidationRequests = {};
		},

		/**
		 * Fetches the value of the form's existing data key.
		 *
		 * This could either be a data-existingDataKey attribute on the form itself,
		 * or a hidden input with class 'ExistingDataKey'
		 *
		 * @return string
		 */
		getExistingDataKey: function()
		{
			var val = this.$form.find('input.ExistingDataKey, select.ExistingDataKey, textarea.ExistingDataKey, button.ExistingDataKey').val();
			if (val === undefined)
			{
				val = this.$form.data('existingdatakey');
				if (val === undefined)
				{
					val = '';
				}
			}

			return val;
		},

		/**
		 * Intercepts form reset events.
		 * If the form specifies a data-exitUrl, the browser will navigate there before resetting the form.
		 *
		 * @param event e
		 */
		formReset: function(e)
		{
			var exitUrl = this.$form.data('exiturl');

			if (exitUrl)
			{
				XenForo.redirect(exitUrl);
			}
		},

		/**
		 * Fires whenever a submit button is clicked, in order to store the clicked control
		 *
		 * @param event e
		 */
		setClickedSubmit: function(e)
		{
			this.$form.data('clickedsubmitbutton', e.target);
		},

		editorAutoSave: function(e)
		{
			if (this.submitPending)
			{
				e.preventDefault();
			}
		},

		/**
		 * Intercepts form submit events.
		 * Attempts to save the form with AJAX, after cancelling any pending validation tasks.
		 *
		 * @param event e
		 *
		 * @return boolean false
		 */
		ajaxSave: function(e)
		{
			if (!this.ajaxSubmit || !XenForo._enableAjaxSubmit)
			{
				// do normal validation
				return true;
			}

			this.abortPendingFieldValidation();

			var clickedSubmitButton = this.$form.data('clickedsubmitbutton'),
				serialized,
				$clickedSubmitButton,

			/**
			 * Event listeners for this event can:
			 * 	e.preventSubmit = true; to prevent any submission
			 * 	e.preventDefault(); to disable ajax sending
			 */
			eDataSend = $.Event('AutoValidationBeforeSubmit');
				eDataSend.formAction = this.$form.attr('action');
				eDataSend.clickedSubmitButton = clickedSubmitButton;
				eDataSend.preventSubmit = false;
				eDataSend.ajaxOptions = {};

			this.$form.trigger(eDataSend);

			this.$form.removeData('clickedSubmitButton');

			if (eDataSend.preventSubmit)
			{
				return false;
			}
			else if (!eDataSend.isDefaultPrevented())
			{
				serialized = this.$form.serializeArray();
				if (clickedSubmitButton)
				{
					$clickedSubmitButton = $(clickedSubmitButton);
					if ($clickedSubmitButton.attr('name'))
					{
						serialized.push({
							name: $clickedSubmitButton.attr('name'),
							value: $clickedSubmitButton.attr('value')
						});
					}
				}

				this.submitPending = true;

				XenForo.ajax(
					eDataSend.formAction,
					serialized,
					$.context(this, 'ajaxSaveResponse'),
					eDataSend.ajaxOptions
				);

				e.preventDefault();
			}
		},

		/**
		 * Handles the AJAX response from ajaxSave().
		 *
		 * @param ajaxData
		 * @param textStatus
		 * @return
		 */
		ajaxSaveResponse: function(ajaxData, textStatus)
		{
			this.submitPending = false;

			if (!ajaxData)
			{
				console.warn('No ajax data returned.');
				return false;
			}

			var eDataRecv,
				eError,
				eComplete,
				$trigger;

			eDataRecv = $.Event('AutoValidationDataReceived');
			eDataRecv.ajaxData = ajaxData;
			eDataRecv.textStatus = textStatus;
			eDataRecv.validationError = [];
			console.group('Event: %s', eDataRecv.type);
			this.$form.trigger(eDataRecv);
			console.groupEnd();
			if (eDataRecv.isDefaultPrevented())
			{
				return false;
			}

			// if the submission has failed validation, show the error overlay
			if (!this.validates(eDataRecv))
			{
				eError = $.Event('AutoValidationError');
				eError.ajaxData = ajaxData;
				eError.textStatus = textStatus;
				eError.validationError = eDataRecv.validationError;
				console.group('Event: %s', eError.type);
				this.$form.trigger(eError);
				console.groupEnd();
				if (eError.isDefaultPrevented())
				{
					return false;
				}

				if (this.$form.closest('.xenOverlay').length)
				{
					this.$form.closest('.xenOverlay').data('overlay').close();
				}

				if (ajaxData.errorTemplateHtml)
				{
					new XenForo.ExtLoader(ajaxData, function(data) {
						var $overlayHtml = XenForo.alert(
							ajaxData.errorTemplateHtml,
							XenForo.phrases.following_error_occurred + ':'
						);
						if ($overlayHtml)
						{
							$overlayHtml.find('div.errorDetails').removeClass('baseHtml');
							if (ajaxData.errorOverlayType)
							{
								$overlayHtml.closest('.errorOverlay').removeClass('errorOverlay').addClass(ajaxData.errorOverlayType);
							}
						}
					});
				}
				else if (ajaxData.templateHtml)
				{
					setTimeout($.context(function()
					{
						this.$error = XenForo.createOverlay(null, this.prepareError(ajaxData.templateHtml)).load();
					}, this), 250);
				}
				else if (ajaxData.error !== undefined)
				{
					if (typeof ajaxData.error === 'object')
					{
						var key;
						for (key in ajaxData.error)
						{
							break;
						}
						ajaxData.error = ajaxData.error[key];
					}

					XenForo.alert(
						ajaxData.error + '\n'
							+ (ajaxData.traceHtml !== undefined ? '<ol class="traceHtml">\n' + ajaxData.traceHtml + '</ol>' : ''),
						XenForo.phrases.following_error_occurred + ':'
					);
				}

				return false;
			}

			eComplete = $.Event('AutoValidationComplete'),
			eComplete.ajaxData = ajaxData;
			eComplete.textStatus = textStatus;
			eComplete.$form = this.$form;
			console.group('Event: %s', eComplete.type);
			this.$form.trigger(eComplete);
			console.groupEnd();
			if (eComplete.isDefaultPrevented())
			{
				return false;
			}

			// if the form is in an overlay, close it
			if (this.$form.parents('.xenOverlay').length)
			{
				this.$form.parents('.xenOverlay').data('overlay').close();

				if (ajaxData.linkPhrase)
				{
					$trigger = this.$form.parents('.xenOverlay').data('overlay').getTrigger();
					$trigger.xfFadeOut(XenForo.speed.fast, function()
					{
						if (ajaxData.linkUrl && $trigger.is('a'))
						{
							$trigger.attr('href', ajaxData.linkUrl);
						}

						$trigger
							.text(ajaxData.linkPhrase)
							.xfFadeIn(XenForo.speed.fast);
					});
				}
			}

			if (XenForo.isPositive(this.$form.data('reset')))
			{
				this.$form[0].reset();
			}

			if (ajaxData.message)
			{
				XenForo.alert(ajaxData.message, '', 4000);
				return;
			}

			// if a redirect message was not specified, redirect immediately
			if (ajaxData._redirectMessage == '')
			{
				this.submitPending = true;
				return this.redirect(ajaxData._redirectTarget);
			}

			// show the redirect message, then redirect if a redirect target was specified
			this.submitPending = true;
			XenForo.alert(ajaxData._redirectMessage, '', 1000, $.context(function()
			{
				this.redirect(ajaxData._redirectTarget);
			}, this));
		},

		/**
		 * Checks for the presence of validation errors in the given event
		 *
		 * @param event e
		 *
		 * @return boolean
		 */
		validates: function(e)
		{
			return ($.isEmptyObject(e.validationErrors) && !e.ajaxData.error);
		},

		/**
		 * Attempts to match labels to errors for the error overlay
		 *
		 * @param string html
		 *
		 * @return jQuery
		 */
		prepareError: function(html)
		{
			$html = $(html);

			// extract labels that correspond to the error fields and insert their text next to the error message
			$html.find('label').each(function(i, label)
			{
				var $ctrlLabel = $('#' + $(label).attr('for'))
					.closest('.ctrlUnit')
					.find('dt > label');

				if ($ctrlLabel.length)
				{
					$(label).prepend($ctrlLabel.text() + '<br />');
				}
			});

			return $html;
		},

		/**
		 * Redirect the browser to redirectTarget if it is specified
		 *
		 * @param string redirectTarget
		 *
		 * @return boolean
		 */
		redirect: function(redirectTarget)
		{
			if (XenForo.isPositive(this.$form.data('redirect')) || !parseInt(XenForo._enableOverlays))
			{
				var $AutoValidationRedirect = new $.Event('AutoValidationRedirect');
					$AutoValidationRedirect.redirectTarget = redirectTarget;

				this.$form.trigger($AutoValidationRedirect);

				if (!$AutoValidationRedirect.isDefaultPrevented() && $AutoValidationRedirect.redirectTarget)
				{
					var fn = function()
					{
						XenForo.redirect(redirectTarget);
					};

					if (XenForo._manualDeferOverlay)
					{
						$(document).one('ManualDeferComplete', fn);
					}
					else
					{
						fn();
					}

					return true;
				}
			}

			return false;
		},

		// ---------------------------------------------------
		// Field validation methods...

		/**
		 * Sets a timeout before an AJAX field validation request will be fired
		 * (Prevents AJAX floods)
		 *
		 * @param string Name of field to be validated
		 * @param function Callback to fire when the timeout elapses
		 */
		setFieldValidationTimeout: function(name, callback)
		{
			if (!this.hasFieldValidator(name)) { return false; }

			console.log('setTimeout %s', name);

			this.clearFieldValidationTimeout(name);

			this.fieldValidationTimeouts[name] = setTimeout(callback, 250);
		},

		/**
		 * Cancels a timeout set with setFieldValidationTimeout()
		 *
		 * @param string name
		 */
		clearFieldValidationTimeout: function(name)
		{
			if (this.fieldValidationTimeouts[name])
			{
				console.log('Clear field validation timeout: %s', name);

				clearTimeout(this.fieldValidationTimeouts[name]);
				delete(this.fieldValidationTimeouts[name]);
			}
		},

		/**
		 * Fires an AJAX field validation request
		 *
		 * @param string Name of variable to be verified
		 * @param jQuery Input field to be validated
		 * @param function Callback function to fire on success
		 */
		startFieldValidationRequest: function(name, $input, callback)
		{
			if (!this.hasFieldValidator(name)) { return false; }

			// abort any existing AJAX validation requests from this $input
			this.abortFieldValidationRequest(name);

			// fire the AJAX request and register it in the fieldValidationRequests
			// object so it can be cancelled by subsequent requests
			this.fieldValidationRequests[name] = XenForo.ajax(this.fieldValidatorUrl,
			{
				name: name,
				value: $input.fieldValue(),
				existingDataKey: this.getExistingDataKey()
			}, callback,
			{
				global: false // don't show AJAX progress indicators for inline validation
			});
		},

		/**
		 * Aborts an AJAX field validation request set up by startFieldValidationRequest()
		 *
		 * @param string name
		 */
		abortFieldValidationRequest: function(name)
		{
			if (this.fieldValidationRequests[name])
			{
				console.log('Abort field validation request: %s', name);

				this.fieldValidationRequests[name].abort();
				delete(this.fieldValidationRequests[name]);
			}
		},

		/**
		 * Cancels any pending timeouts or ajax field validation requests
		 */
		abortPendingFieldValidation: function()
		{
			$.each(this.fieldValidationTimeouts, $.context(this, 'clearFieldValidationTimeout'));
			$.each(this.fieldValidationRequests, $.context(this, 'abortFieldValidationRequest'));
		},

		/**
		 * Throws a warning if this.fieldValidatorUrl is not valid
		 *
		 * @param string Name of field to be validated
		 *
		 * @return boolean
		 */
		hasFieldValidator: function(name)
		{
			if (this.fieldValidatorUrl)
			{
				return true;
			}

			//console.warn('Unable to request validation for field "%s" due to lack of fieldValidatorUrl in form tag.', name);
			return false;
		}
	};

	// *********************************************************************

	/**
	 * Handler for individual fields in an AutoValidator form.
	 * Manages individual field validation and inline error display.
	 *
	 * @param jQuery input [text-type]
	 */
	XenForo.AutoValidatorControl = function($input) { this.__construct($input); };
	XenForo.AutoValidatorControl.prototype =
	{
		__construct: function($input)
		{
			this.$form = $input.closest('form.AutoValidator').bind(
			{
				AutoValidationDataReceived: $.context(this, 'handleFormValidation')
			});

			this.$input = $input.bind(
			{
				change:              $.context(this, 'change'),
				AutoValidationError: $.context(this, 'showError'),
				AutoValidationPass:  $.context(this, 'hideError')
			});

			this.name = $input.data('validatorname') || $input.attr('name');
			this.autoValidate = $input.hasClass('NoAutoValidate') ? false : true;
		},

		/**
		 * When the value of a field changes, initiate validation
		 *
		 * @param event e
		 */
		change: function(e)
		{
			if (this.autoValidate)
			{
				this.$form.data('XenForo.AutoValidator')
					.setFieldValidationTimeout(this.name, $.context(this, 'validate'));
			}
		},

		/**
		 * Fire a validation AJAX request
		 */
		validate: function()
		{
			if (this.autoValidate)
			{
				this.$form.data('XenForo.AutoValidator')
					.startFieldValidationRequest(this.name, this.$input, $.context(this, 'handleValidation'));
			}
		},

		/**
		 * Handle the data returned from an AJAX validation request fired in validate().
		 * Fires 'AutoValidationPass' or 'AutoValidationError' for the $input according to the validation state.
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 *
		 * @return boolean
		 */
		handleValidation: function(ajaxData, textStatus)
		{
			if (ajaxData && ajaxData.error && ajaxData.error.hasOwnProperty(this.name))
			{
				this.$input.trigger({
					type: 'AutoValidationError',
					errorMessage: ajaxData.error[this.name]
				});
				return false;
			}
			else
			{
				this.$input.trigger('AutoValidationPass');
				return true;
			}
		},

		/**
		 * Shows an inline error message, text contained within a .errorMessage property of the event passed
		 *
		 * @param event e
		 */
		showError: function(e)
		{
			console.warn('%s: %s', this.name, e.errorMessage);

			var error = this.fetchError(e.errorMessage).css('display', 'inline-block');
			this.positionError(error);
		},

		/**
		 * Hides any inline error message shown with this input
		 */
		hideError: function()
		{
			console.info('%s: Okay', this.name);

			if (this.$error)
			{
				this.fetchError()
					.hide();
			}
		},

		/**
		 * Fetches or creates (as necessary) the error HTML object for this field
		 *
		 * @param string Error message
		 *
		 * @return jQuery this.$error
		 */
		fetchError: function(message)
		{
			if (!this.$error)
			{
				this.$error = $('<label for="' + this.$input.attr('id') + '" class="formValidationInlineError">WHoops</label>').insertAfter(this.$input);
			}

			if (message)
			{
				this.$error.html(message).xfActivate();
			}

			return this.$error;
		},

		/**
		 * Returns an object containing top and left properties, used to position the inline error message
		 */
		positionError: function($error)
		{
			$error.removeClass('inlineError');

			var coords = this.$input.coords('outer', 'position'),
				screenCoords = this.$input.coords('outer'),
				$window = $(window),
				outerWidth = $error.outerWidth(),
				absolute,
				position = { top: coords.top };
			
			if (!screenCoords.width || !screenCoords.height)
			{
				absolute = false;
			}
			else
			{
				if (XenForo.isRTL())
				{
					position.left = coords.left - outerWidth - 10;
					absolute = (screenCoords.left - outerWidth - 10 > 0);
				}
				else
				{
					var screenLeft = screenCoords.left + screenCoords.width + 10;

					absolute = screenLeft + outerWidth < ($window.width() + $window.scrollLeft());
					position.left = coords.left + coords.width + 10;
				}
			}

			if (absolute)
			{
				$error.css(position);
			}
			else
			{
				$error.addClass('inlineError');
			}
		},

		/**
		 * Handles validation for this field passed down from a submission of the whole AutoValidator
		 * form, and passes the relevant data into the handler for this field specifically.
		 *
		 * @param event e
		 */
		handleFormValidation: function(e)
		{
			if (!this.handleValidation(e.ajaxData, e.textStatus))
			{
				e.validationError.push(this.name);
			}
		}
	};

	// *********************************************************************

	/**
	 * Checks a form field to see if it is part of an AutoValidator form,
	 * and if so, whether or not it is subject to autovalidation.
	 *
	 * @param object Form control to be tested
	 *
	 * @return boolean
	 */
	XenForo.isAutoValidatorField = function(ctrl)
	{
		var AutoValidator, $ctrl, $form = $(ctrl.form);

		if (!$form.hasClass('AutoValidator'))
		{
			return false;
		}

		AutoValidator = $form.data('XenForo.AutoValidator');

		if (AutoValidator)
		{
			$ctrl = $(ctrl);

			switch (AutoValidator.optInMode)
			{
				case 'OptIn':
				{
					return ($ctrl.hasClass('OptIn') || $ctrl.closest('.ctrlUnit').hasClass('OptIn'));
				}
				default:
				{
					return (!$ctrl.hasClass('OptOut') && !$ctrl.closest('.ctrlUnit').hasClass('OptOut'));
				}
			}
		}

		return false;
	};

	// *********************************************************************

	XenForo.PreviewForm = function($form)
	{
		var previewUrl = $form.data('previewurl');
		if (!previewUrl)
		{
			console.warn('PreviewForm has no data-previewUrl: %o', $form);
			return;
		}

		$form.find('.PreviewButton').click(function(e)
		{
			var $button = $(this);

			XenForo.ajax(previewUrl, $form.serialize(), function(ajaxData)
			{
				if (XenForo.hasResponseError(ajaxData) || !XenForo.hasTemplateHtml(ajaxData))
				{
					return false;
				}

				new XenForo.ExtLoader(ajaxData, function(ajaxData)
				{
					var $preview = $form.find('.PreviewContainer').first();
					if ($preview.length)
					{
						$preview.xfFadeOut(XenForo.speed.fast, function() {
							$preview.html(ajaxData.templateHtml).xfActivate();
						});
					}
					else
					{
						$preview = $('<div />', { 'class': 'PreviewContainer'})
							.hide()
							.html(ajaxData.templateHtml)
							.prependTo($form)
							.xfActivate();
					}

					var overlay = $button.data('overlay');
					if (overlay)
					{
						$preview.show();
						XenForo.createOverlay($preview, $preview.html(ajaxData.templateHtml)).load();
					}
					else
					{
						$preview.xfFadeIn(XenForo.speed.fast);
						$preview.get(0).scrollIntoView(true);
					}
				});
			});
		});
	};

	// *********************************************************************

	/**
	 * Allows a text input field to rewrite the H1 (or equivalent) tag's contents
	 *
	 * @param jQuery input[data-liveTitleTemplate]
	 */
	XenForo.LiveTitle = function($input)
	{
		var $title = $input.closest('.formOverlay').find('h2.h1'), setTitle;

		if (!$title.length)
		{
			$title = $('.titleBar h1').first();
		}
		console.info('Title Element: %o', $title);
		$title.data('originalhtml', $title.html());

		setTitle = function(value)
		{
			$input.trigger('LiveTitleSet', [value]);

			$title.html(value === ''
				? $title.data('originalhtml')
				: $input.data('livetitletemplate').replace(/%s/, $('<div />').text(value).html())
			);
		};

		if (!$input.hasClass('prompt'))
		{
			setTitle($input.strval());
		}

		$input.bind('keyup focus', function(e)
		{
			setTitle($input.strval());
		})
		.on('paste', function(e)
		{
			setTimeout(function()
			{
				setTitle($input.strval());
			}, 0);
		})
		.closest('form').bind('reset', function(e)
		{
			setTitle('');
		});
	};

	// *********************************************************************

	XenForo.TextareaElastic = function($input) { this.__construct($input); };
	XenForo.TextareaElastic.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input;
			this.curHeight = 0;

			$input.bind('keyup focus XFRecalculate', $.context(this, 'recalculate'));
			$input.bind('paste', $.context(this, 'paste'));

			if ($input.val() !== '')
			{
				this.recalculate();
			}
		},

		recalculate: function()
		{
			var $input = this.$input,
				input = $input.get(0),
				clone,
				height,
				pos;

			if ($input.val() === '')
			{
				$input.css({
					'overflow-y': 'hidden',
					'height': ''
				});
				this.curHeight = 0;
				return;
			}

			if (!input.clientWidth)
			{
				return;
			}

			if (!this.minHeight)
			{
				this.borderBox = ($input.css('-moz-box-sizing') == 'border-box' || $input.css('box-sizing') == 'border-box');
				this.minHeight = (this.borderBox ? $input.outerHeight() : input.clientHeight);

				if (!this.minHeight)
				{
					return;
				}

				this.maxHeight = parseInt($input.css('max-height'), 10);
				this.spacing = (this.borderBox ? $input.outerHeight() - $input.innerHeight() : 0);
			}

			if (!this.$clone)
			{
				this.$clone = $('<textarea />').css({
					position: 'absolute',
					left: (XenForo.isRTL() ? '9999em' : '-9999em'),
					top: 0,
					visibility: 'hidden',
					width: input.clientWidth,
					height: '1px',
					'font-size': $input.css('font-size'),
					'font-family': $input.css('font-family'),
					'font-weight': $input.css('font-weight'),
					'line-height': $input.css('line-height'),
					'word-wrap': $input.css('word-wrap')
				}).attr('tabindex', -1).val(' ');

				this.$clone.appendTo(document.body);

				this.lineHeight = this.$clone.get(0).scrollHeight;
			}

			this.$clone.val($input.val());
			clone = this.$clone.get(0);

			height = Math.max(this.minHeight, clone.scrollHeight + this.lineHeight + this.spacing);

			if (height < this.maxHeight)
			{
				if (this.curHeight != height)
				{
					input = $input.get(0);
					if (this.curHeight == this.maxHeight && input.setSelectionRange)
					{
						pos = input.selectionStart;
					}

					$input.css({
						'overflow-y': 'hidden',
						'height': height + 'px'
					});

					if (this.curHeight == this.maxHeight && input.setSelectionRange)
					{
						try
						{
							input.setSelectionRange(pos, pos);
						} catch(e) {}
					}

					this.curHeight = height;
				}
			}
			else
			{
				if (this.curHeight != this.maxHeight)
				{
					input = $input.get(0);
					if (input.setSelectionRange)
					{
						pos = input.selectionStart;
					}

					$input.css({
						'overflow-y': 'auto',
						'height': this.maxHeight + 'px'
					});

					if (input.setSelectionRange)
					{
						try
						{
							input.setSelectionRange(pos, pos);
						} catch (e) {}
					}

					this.curHeight = this.maxHeight;
				}
			}
		},

		paste: function()
		{
			setTimeout($.context(this, 'recalculate'), 100);
		}
	};

	// *********************************************************************

	XenForo.AutoTimeZone = function($element)
	{
		var now = new Date(),
			jan1 = new Date(now.getFullYear(), 0, 1), // 0 = jan
			jun1 = new Date(now.getFullYear(), 5, 1), // 5 = june
			jan1offset = Math.round(jan1.getTimezoneOffset()),
			jun1offset = Math.round(jun1.getTimezoneOffset());

		// opera doesn't report TZ offset differences in jan/jun correctly
		if ($.browser.opera)
		{
			return false;
		}

		if (XenForo.AutoTimeZone.map[jan1offset + ',' + jun1offset])
		{
			$element.val(XenForo.AutoTimeZone.map[jan1offset + ',' + jun1offset]);
			return true;
		}
		else
		{
			return false;
		}
	};

	XenForo.AutoTimeZone.map =
	{
		'660,660' : 'Pacific/Midway',
		'600,600' : 'Pacific/Honolulu',
		'570,570' : 'Pacific/Marquesas',
		'540,480' : 'America/Anchorage',
		'480,420' : 'America/Los_Angeles',
		'420,360' : 'America/Denver',
		'420,420' : 'America/Phoenix',
		'360,300' : 'America/Chicago',
		'360,360' : 'America/Belize',
		'300,240' : 'America/New_York',
		'300,300' : 'America/Bogota',
		'270,270' : 'America/Caracas',
		'240,180' : 'America/Halifax',
		'180,240' : 'America/Cuiaba',
		'240,240' : 'America/La_Paz',
		'210,150' : 'America/St_Johns',
		'180,180' : 'America/Argentina/Buenos_Aires',
		'120,180' : 'America/Sao_Paulo',
		'180,120' : 'America/Miquelon',
		'120,120' : 'America/Noronha',
		'60,60' : 'Atlantic/Cape_Verde',
		'60,0' : 'Atlantic/Azores',
		'0,-60' : 'Europe/London',
		'0,0' : 'Atlantic/Reykjavik',
		'-60,-120' : 'Europe/Amsterdam',
		'-60,-60' : 'Africa/Algiers',
		'-120,-60' : 'Africa/Windhoek',
		'-120,-180' : 'Europe/Athens',
		'-120,-120' : 'Africa/Johannesburg',
		'-180,-240' : 'Africa/Nairobi',
		'-180,-180' : 'Europe/Moscow',
		'-210,-270' : 'Asia/Tehran',
		'-240,-300' : 'Asia/Yerevan',
		'-270,-270' : 'Asia/Kabul',
		'-300,-300' : 'Asia/Tashkent',
		'-330,-330' : 'Asia/Kolkata',
		'-345,-345' : 'Asia/Kathmandu',
		'-360,-360' : 'Asia/Dhaka',
		'-390,-390' : 'Asia/Rangoon',
		'-420,-420' : 'Asia/Bangkok',
		'-420,-480' : 'Asia/Krasnoyarsk',
		'-480,-480' : 'Asia/Hong_Kong',
		'-540,-540' : 'Asia/Tokyo',
		'-630,-570' : 'Australia/Adelaide',
		'-570,-570' : 'Australia/Darwin',
		'-660,-600' : 'Australia/Sydney',
		'-600,-600' : 'Asia/Vladivostok',
		'-690,-690' : 'Pacific/Norfolk',
		'-780,-720' : 'Pacific/Auckland',
		'-825,-765' : 'Pacific/Chatham',
		'-780,-780' : 'Pacific/Tongatapu',
		'-840,-840' : 'Pacific/Kiritimati'
	};

	// *********************************************************************

	XenForo.DatePicker = function($input)
	{
		if (!XenForo.DatePicker.$root)
		{
			$.tools.dateinput.localize('_f',
			{
				months: XenForo.phrases._months,
				shortMonths: '1,2,3,4,5,6,7,8,9,10,11,12',
				days: 's,m,t,w,t,f,s',
				shortDays: XenForo.phrases._daysShort
			});
		}

		var $date = $input.dateinput(
		{
			lang: '_f',
			format: 'yyyy-mm-dd', // rfc 3339 format, required by html5 date element
			speed: 0,
			yearRange: [-100, 100],
			onShow: function(e)
			{
				var $root = XenForo.DatePicker.$root,
					offset = $date.offset(),
					maxZIndex = 0,
					position = { top: offset.top + $date.outerHeight() };

				if (XenForo.isRTL())
				{
					position.right = $('html').width() - offset.left - $date.outerWidth();
				}
				else
				{
					position.left = offset.left;
				}

				$root.css(position);

				$date.parents().each(function(i, el)
				{
					var zIndex = parseInt($(el).css('z-index'), 10);
					if (zIndex > maxZIndex)
					{
						maxZIndex = zIndex;
					}
				});

				$root.css('z-index', maxZIndex + 1000);
			}
		});

		$date.addClass($input.attr('class'));
		if ($input.attr('id'))
		{
			$date.attr('id', $input.attr('id'));
		}

		// this is needed to handle input[type=reset] buttons that end up focusing the field
		$date.closest('form').on('reset', function() {
			setTimeout(function() {
				$date.data('dateinput').hide();
			}, 10);
			setTimeout(function() {
				$date.data('dateinput').hide();
			}, 100);
		});

		if (!XenForo.DatePicker.$root)
		{
			XenForo.DatePicker.$root = $('#calroot').appendTo(document.body);

			$('#calprev').html(XenForo.isRTL() ? '&rarr;' : '&larr;').prop('unselectable', true);
			$('#calnext').html(XenForo.isRTL() ? '&larr;' : '&rarr;').prop('unselectable', true);
		}
	};

	XenForo.DatePicker.$root = null;

	// *********************************************************************

	XenForo.AutoComplete = function($element) { this.__construct($element); };
	XenForo.AutoComplete.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input;

			this.url = $input.data('acurl') || XenForo.AutoComplete.getDefaultUrl();

			if (this.$input.hasClass("TeamSelect"))
			{
				this.url = 'index.php?groups/find&_xfResponseType=json';
			}

			this.extraFields = $input.data('acextrafields');

			var options = {
				multiple: $input.hasClass('AcSingle') ? false : ',', // mutiple value joiner
				minLength: 2, // min word length before lookup
				queryKey: 'q',
				extraParams: {},
				jsonContainer: 'results',
				autoSubmit: XenForo.isPositive($input.data('autosubmit'))
			};
			if ($input.data('acoptions'))
			{
				options = $.extend(options, $input.data('acoptions'));
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

			this.loadVal = '';
			this.results = new XenForo.AutoCompleteResults({
				onInsert: $.context(this, 'addValue')
			});

			$input.attr('autocomplete', 'off')
				.keydown($.context(this, 'keystroke'))
				.keypress($.context(this, 'operaKeyPress'))
				.blur($.context(this, 'blur'));

			$input.on('paste', function()
			{
				setTimeout(function()
				{
					$input.trigger('keydown');
				}, 0);
			});

			$input.closest('form').submit($.context(this, 'hideResults'));
		},

		keystroke: function(e)
		{
			var code = e.keyCode || e.charCode, prevent = true;

			switch(code)
			{
				case 40: this.results.selectResult(1); break; // down
				case 38: this.results.selectResult(-1); break; // up
				case 27: this.results.hideResults(); break; // esc
				case 13: // enter
					if (this.results.isVisible())
					{
						this.results.insertSelectedResult();
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

					if (code != 229)
					{
						this.results.hideResults();
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
			this.results.hideResults();
		},

		showResults: function(results)
		{
			if (this.xhr)
			{
				this.xhr = false;
			}

			if (this.jsonContainer && results)
			{
				results = results[this.jsonContainer];
			}

			this.results.showResults(this.getPartialValue(), results, this.$input);
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
			this.$input.trigger("AutoComplete", {inserted: value, current: this.$input.val()});

			if (this.autoSubmit)
			{
				this.$input.closest('form').submit();
			}
			else
			{
				this.$input.focus();
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
	XenForo.AutoComplete.getDefaultUrl = function()
	{
		if (XenForo.AutoComplete.defaultUrl === null)
		{
			if ($('html').hasClass('Admin'))
			{
				XenForo.AutoComplete.defaultUrl = 'admin.php?users/search-name&_xfResponseType=json';
			}
			else
			{
				XenForo.AutoComplete.defaultUrl = 'index.php?members/find&_xfResponseType=json';
			}

		};
		return XenForo.AutoComplete.defaultUrl;
	};
	XenForo.AutoComplete.defaultUrl = null;

	// *********************************************************************

	XenForo.UserTagger = function($element) { this.__construct($element); };
	XenForo.UserTagger.prototype =
	{
		__construct: function($textarea)
		{
			this.$textarea = $textarea;
			this.url = $textarea.data('acurl') || XenForo.AutoComplete.getDefaultUrl();
			this.acResults = new XenForo.AutoCompleteResults({
				onInsert: $.context(this, 'insertAutoComplete')
			});

			var self = this,
				hideCallback = function() {
				setTimeout(function() {
					self.acResults.hideResults();
				}, 200);
			};

			$(document).on('scroll', hideCallback);

			$textarea.on('click blur', hideCallback);
			$textarea.on('keydown', function(e) {
				var prevent = true,
					acResults = self.acResults;

				if (!acResults.isVisible())
				{
					return;
				}

				switch (e.keyCode)
				{
					case 40: acResults.selectResult(1); break; // down
					case 38: acResults.selectResult(-1); break; // up
					case 27: acResults.hideResults(); break; // esc
					case 13: acResults.insertSelectedResult(); break; // enter

					default:
						prevent = false;
				}

				if (prevent)
				{
					e.stopPropagation();
					e.stopImmediatePropagation();
					e.preventDefault();
				}
			});
			$textarea.on('keyup', function(e) {
				var autoCompleteText = self.findCurrentAutoCompleteOption();
				if (autoCompleteText)
				{
					self.triggerAutoComplete(autoCompleteText);
				}
				else
				{
					self.hideAutoComplete();
				}
			});
		},

		findCurrentAutoCompleteOption: function()
		{
			var $textarea = this.$textarea;

			$textarea.focus();
			var sel = $textarea.getSelection(),
				testText,
				lastAt;

			if (!sel || sel.end <= 1)
			{
				return false;
			}

			testText = $textarea.val().substring(0, sel.end);
			lastAt = testText.lastIndexOf('@');

			if (lastAt != -1 && (lastAt == 0 || testText.substr(lastAt - 1, 1).match(/(\s|[\](,]|--)/)))
			{
				var afterAt = testText.substr(lastAt + 1);
				if (!afterAt.match(/\s/) || afterAt.length <= 10)
				{
					return afterAt;
				}
			}

			return false;
		},

		insertAutoComplete: function(name)
		{
			var $textarea = this.$textarea;

			$textarea.focus();
			var sel = $textarea.getSelection(),
				testText;

			if (!sel || sel.end <= 1)
			{
				return false;
			}

			testText = $textarea.val().substring(0, sel.end);

			var lastAt = testText.lastIndexOf('@');
			if (lastAt != -1)
			{
				$textarea.setSelection(lastAt, sel.end);
				$textarea.replaceSelectedText('@' + name + ' ', 'collapseToEnd');
				this.lastAcLookup = name + ' ';
			}
		},

		triggerAutoComplete: function(name)
		{
			if (this.lastAcLookup && this.lastAcLookup == name)
			{
				return;
			}

			this.hideAutoComplete();
			this.lastAcLookup = name;
			if (name.length > 2 && name.substr(0, 1) != '[')
			{
				this.acLoadTimer = setTimeout($.context(this, 'autoCompleteLookup'), 200);
			}
		},

		autoCompleteLookup: function()
		{
			if (this.acXhr)
			{
				this.acXhr.abort();
			}

			if (this.lastAcLookup != this.findCurrentAutoCompleteOption())
			{
				return;
			}

			this.acXhr = XenForo.ajax(
				this.url,
				{ q: this.lastAcLookup },
				$.context(this, 'showAutoCompleteResults'),
				{ global: false, error: false }
			);
		},

		showAutoCompleteResults: function(ajaxData)
		{
			this.acXhr = false;
			this.acResults.showResults(
				this.lastAcLookup,
				ajaxData.results,
				this.$textarea
			);
		},

		hideAutoComplete: function()
		{
			this.acResults.hideResults();

			if (this.acLoadTimer)
			{
				clearTimeout(this.acLoadTimer);
				this.acLoadTimer = false;
			}
		}
	};

	// *********************************************************************

	XenForo.AutoCompleteResults = function(options) { this.__construct(options); };
	XenForo.AutoCompleteResults.prototype =
	{
		__construct: function(options)
		{
			this.options = $.extend({
				onInsert: false
			}, options);

			this.selectedResult = 0;
			this.$results = false;
			this.resultsVisible = false;
			this.resizeBound = false;
		},

		isVisible: function()
		{
			return this.resultsVisible;
		},

		hideResults: function()
		{
			this.resultsVisible = false;

			if (this.$results)
			{
				this.$results.hide();
			}
		},

		showResults: function(val, results, $targetOver, cssPosition)
		{
			var maxZIndex = 0,
				i,
				filterRegex,
				result,
				$li;

			if (!results)
			{
				this.hideResults();
				return;
			}

			this.resultsVisible = false;

			if (!this.$results)
			{
				this.$results = $('<ul />')
					.css({position: 'absolute', display: 'none'})
					.addClass('autoCompleteList')
					.appendTo(document.body);

				$targetOver.parents().each(function(i, el)
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

			filterRegex = new RegExp('(' + XenForo.regexQuote(val) + ')', 'i');

			for (i in results)
			{
				if (!results.hasOwnProperty(i))
				{
					continue;
				}

				result = results[i];

				$li = $('<li />')
					.css('cursor', 'pointer')
					.attr('unselectable', 'on')
					.data('autocomplete', i)
					.click($.context(this, 'resultClick'))
					.mouseenter($.context(this, 'resultMouseEnter'));

				function viewResultName(filterRegex, value, domObj)
				{
					return domObj.html(value.replace(filterRegex, '<strong>$1</strong>'));
				}

				function viewUser(userObj){
					viewResultName(filterRegex,userObj['username'],$li)
						.prepend($('<img class="autoCompleteAvatar" />').attr('src', userObj['avatar']));
				}

				if (typeof result == 'string')
				{
					$li.html(XenForo.htmlspecialchars(result).replace(filterRegex, '<strong>$1</strong>'));
				}
				else
				{
					if (result["object"] != null)
					{
						switch (result["object"]){
							case "team" : {
								viewResultName(filterRegex, result["title"], $li)
									.prepend($('<img class="autoCompleteAvatar" />').attr('src', result['logo']));
							}
						}
					}
					else {
						viewUser(result);
					}
				}

				$li.appendTo(this.$results);
			}

			if (!this.$results.children().length)
			{
				return;
			}

			this.selectResult(0, true);

			if (!this.resizeBound)
			{
				$(window).bind('resize', $.context(this, 'hideResults'));
			}

			if (!cssPosition)
			{
				var offset = $targetOver.offset();

				cssPosition = {
					top: offset.top + $targetOver.outerHeight(),
					left: offset.left
				};

				if (XenForo.isRTL())
				{
					cssPosition.right = $('html').width() - offset.left - $targetOver.outerWidth();
					cssPosition.left = 'auto';
				}
			}

			this.$results.css(cssPosition).show();
			this.resultsVisible = true;
		},

		resultClick: function(e)
		{
			e.stopPropagation();

			this.insertResult($(e.currentTarget).data('autocomplete'));
			this.hideResults();
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
					this.insertResult($(res).data('autocomplete'));
					ret = true;
				}
			}

			this.hideResults();

			return ret;
		},

		insertResult: function(value)
		{
			if (this.options.onInsert)
			{
				this.options.onInsert(value);
			}
		}
	};

	// *********************************************************************

	XenForo.AutoSelect = function($input)
	{
		$input.bind('focus', function(e)
		{
			setTimeout(function() { $input.select(); }, 50);
		});
	};

	// *********************************************************************

	/**
	 * Status Editor
	 *
	 * @param jQuery $textarea.StatusEditor
	 */
	XenForo.StatusEditor = function($input) { this.__construct($input); };
	XenForo.StatusEditor.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input
				.keyup($.context(this, 'update'))
				.keydown($.context(this, 'preventNewline'));

			this.$counter = $(this.$input.data('statuseditorcounter'));
			if (!this.$counter.length)
			{
				this.$counter = $('<span />').insertAfter(this.$input);
			}
			this.$counter
				.addClass('statusEditorCounter')
				.text('0');

			this.$form = this.$input.closest('form').bind(
			{
				AutoValidationComplete: $.context(this, 'saveStatus')
			});

			this.charLimit = 140; // Twitter max characters
			this.charCount = 0; // number of chars currently in use

			this.update();
		},

		/**
		 * Handles key events on the status editor, updates the 'characters remaining' output.
		 *
		 * @param Event e
		 */
		update: function(e)
		{
			var statusText = this.$input.val();

			if (this.$input.attr('placeholder') && this.$input.attr('placeholder') == statusText)
			{
				this.setCounterValue(this.charLimit, statusText.length);
			}
			else
			{
				this.setCounterValue(this.charLimit - statusText.length, statusText.length);
			}
		},

		/**
		 * Sets the value of the character countdown, and appropriate classes for that value.
		 *
		 * @param integer Characters remaining
		 * @param integer Current length of status text
		 */
		setCounterValue: function(remaining, length)
		{
			if (remaining < 0)
			{
				this.$counter.addClass('error');
				this.$counter.removeClass('warning');
			}
			else if (remaining <= this.charLimit - 130)
			{
				this.$counter.removeClass('error');
				this.$counter.addClass('warning');
			}
			else
			{
				this.$counter.removeClass('error');
				this.$counter.removeClass('warning');
			}

			this.$counter.text(remaining);
			this.charCount = length || this.$input.val().length;
		},

		/**
		 * Don't allow newline characters in the status message.
		 *
		 * Submit the form if [Enter] or [Return] is hit.
		 *
		 * @param Event e
		 */
		preventNewline: function(e)
		{
			if (e.which == 13) // return / enter
			{
				e.preventDefault();

				$(this.$input.get(0).form).submit();

				return false;
			}
		},

		/**
		 * Updates the status field after saving
		 *
		 * @param event e
		 */
		saveStatus: function(e)
		{
			this.$input.val('');
			this.update(e);

			if (e.ajaxData && e.ajaxData.status !== undefined)
			{
				$('.CurrentStatus').text(e.ajaxData.status);
			}
		}
	};

	// *********************************************************************

	/**
	 * Special effect that allows positioning based on bottom / left rather than top / left
	 */
	$.tools.tooltip.addEffect('PreviewTooltip',
	function(callback)
	{
		var triggerOffset = this.getTrigger().offset(),
			config = this.getConf(),
			css = {
				top: 'auto',
				bottom: $(window).height() - triggerOffset.top + config.offset[0]
			},
			narrowScreen = ($(window).width() < 480);

		if (XenForo.isRTL())
		{
			css.right = $('html').width() - this.getTrigger().outerWidth() - triggerOffset.left - config.offset[1];
			css.left = 'auto';
		}
		else
		{
			css.left = triggerOffset.left + config.offset[1];
			if (narrowScreen)
			{
				css.left = Math.min(50, css.left);
			}
		}

		this.getTip().css(css).xfFadeIn(XenForo.speed.normal);

	},
	function(callback)
	{
		this.getTip().xfFadeOut(XenForo.speed.fast);
	});

	/**
	 * Cache to store fetched previews
	 *
	 * @var object
	 */
	XenForo._PreviewTooltipCache = {};

	XenForo.PreviewTooltip = function($el)
	{
		var hasTooltip, previewUrl, setupTimer;

		if (!parseInt(XenForo._enableOverlays))
		{
			return;
		}

		if (!(previewUrl = $el.data('previewurl')))
		{
			console.warn('Preview tooltip has no preview: %o', $el);
			return;
		}

		$el.find('[title]').andSelf().attr('title', '');

		$el.bind(
		{
			mouseenter: function(e)
			{
				if (hasTooltip)
				{
					return;
				}

				setupTimer = setTimeout(function()
				{
					if (hasTooltip)
					{
						return;
					}

					hasTooltip = true;

					var $tipSource = $('#PreviewTooltip'),
						$tipHtml,
						xhr;

					if (!$tipSource.length)
					{
						console.error('Unable to find #PreviewTooltip');
						return;
					}

					console.log('Setup preview tooltip for %s', previewUrl);

					$tipHtml = $tipSource.clone()
						.removeAttr('id')
						.addClass('xenPreviewTooltip')
						.appendTo(document.body);

					if (!XenForo._PreviewTooltipCache[previewUrl])
					{
						xhr = XenForo.ajax(
							previewUrl,
							{},
							function(ajaxData)
							{
								if (XenForo.hasTemplateHtml(ajaxData))
								{
									XenForo._PreviewTooltipCache[previewUrl] = ajaxData.templateHtml;

									$(ajaxData.templateHtml).xfInsert('replaceAll', $tipHtml.find('.PreviewContents'));
								}
								else
								{
									$tipHtml.remove();
								}
							},
							{
								type: 'GET',
								error: false,
								global: false
							}
						);
					}

					$el.tooltip(XenForo.configureTooltipRtl({
						predelay: 500,
						delay: 0,
						effect: 'PreviewTooltip',
						fadeInSpeed: 'normal',
						fadeOutSpeed: 'fast',
						tip: $tipHtml,
						position: 'bottom left',
						offset: [ 10, -15 ] // was 10, 25
					}));

					$el.data('tooltip').show(0);

					if (XenForo._PreviewTooltipCache[previewUrl])
					{
						$(XenForo._PreviewTooltipCache[previewUrl])
							.xfInsert('replaceAll', $tipHtml.find('.PreviewContents'), 'show', 0);
					}
				}, 800);
			},

			mouseleave: function(e)
			{
				if (hasTooltip)
				{
					if ($el.data('tooltip'))
					{
						$el.data('tooltip').hide();
					}

					return;
				}

				if (setupTimer)
				{
					clearTimeout(setupTimer);
				}
			},

			mousedown: function(e)
			{
				// the click will cancel a timer or hide the tooltip
				if (setupTimer)
				{
					clearTimeout(setupTimer);
				}

				if ($el.data('tooltip'))
				{
					$el.data('tooltip').hide();
				}
			}
		});
	};

	// *********************************************************************

	/**
	 * Allows an entire block to act as a link in the navigation popups
	 *
	 * @param jQuery li.PopupItemLink
	 */
	XenForo.PopupItemLink = function($listItem)
	{
		var href = $listItem.find('.PopupItemLink').first().attr('href');

		if (href)
		{
			$listItem
				.addClass('PopupItemLinkActive')
				.click(function(e)
				{
					if ($(e.target).closest('a').length)
					{
						return;
					}
					XenForo.redirect(href);
				});
		}
	};

	// *********************************************************************

	/**
	 * Allows a link or input to load content via AJAX and insert it into the DOM.
	 * The control element to which this is applied must have href or data-href attributes
	 * and a data-target attribute describing a jQuery selector for the element relative to which
	 * the content will be inserted.
	 *
	 * You may optionally provide a data-method attribute to override the default insertion method
	 * of 'appendTo'.
	 *
	 * By default, the control will be unlinked and have its click event unbound after a single use.
	 * Specify data-unlink="false" to prevent this default behaviour.
	 *
	 * Upon successful return of AJAX data, the control element will fire a 'ContentLoaded' event,
	 * including ajaxData and textStatus data properties.
	 */
	XenForo.Loader = function($link)
	{
		var clickHandler = function(e)
		{
			var href = $link.attr('href') || $link.data('href'),
				target = $link.data('target');

			if (href && $(target).length)
			{
				if ($link.closest('a').length)
				{
					e.stopPropagation();
				}

				e.preventDefault();

				if ($link.data('tooltip'))
				{
					$link.data('tooltip').hide();
				}

				XenForo.ajax(href, {}, function(ajaxData, textStatus)
				{
					if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}

					var insertEvent = new $.Event('ContentLoaded');
						insertEvent.ajaxData = ajaxData;
						insertEvent.textStatus = textStatus;

					$link.trigger(insertEvent);

					if (!insertEvent.isDefaultPrevented())
					{
						if (ajaxData.templateHtml)
						{
							new XenForo.ExtLoader(ajaxData, function()
							{
								var method = $link.data('method');

								if (typeof $.fn[method] != 'function')
								{
									method = 'appendTo';
								}

								if (method == 'replaceAll')
								{
									$(ajaxData.templateHtml).xfInsert(method, target, 'show', 0);
								}
								else
								{
									$(ajaxData.templateHtml).xfInsert(method, target);
								}

								if ($link.data('unlink') !== false)
								{
									$link.removeAttr('href').removeData('href').unbind('click', clickHandler);
								}
							});
						}
					}
				});
			}
		};

		$link.bind('click', clickHandler);
	};

	// *********************************************************************

	/**
	 * Allows a control to create a clone of an existing field, like 'add new response' for polls
	 *
	 * @param jQuery $button.FieldAdder[data-source=#selectorOfCloneSource]
	 */
	XenForo.FieldAdder = function($button)
	{
		$($button.data('source')).filter('.PollNonJsInput').remove();

		$button.click(function(e)
		{
			var $source = $($button.data('source')),
				maxFields = $button.data('maxfields'),
				$clone = null;

			console.log('source.length %s, maxfields %s', $source.length, maxFields);

			if ($source.length && (!maxFields || ($source.length < maxFields)))
			{
				$clone = $source.last().clone();
				$clone.find('input:not([type="button"], [type="submit"])').val('').prop('disabled', true);
				$clone.find('.spinBoxButton').remove();
				$button.trigger({
					type: 'FieldAdderClone',
					clone: $clone
				});
				$clone.xfInsert('insertAfter', $source.last(), false, false, function()
				{
					var $inputs = $clone.find('input');
					$inputs.prop('disabled', false);
					$inputs.first().focus().select();

					if (maxFields)
					{
						if ($($button.data('source')).length >= maxFields)
						{
							$button.xfRemove();
						}
					}
				});
			}
		});
	};

	// *********************************************************************

	/**
	 * Quick way to toggle the read status of an item
	 *
	 * @param jQuery a.ReadToggle
	 */
	XenForo.ReadToggle = function($link)
	{
		$link.click(function(e)
		{
			e.preventDefault();

			var xhr = null,
				$items = null,
				counterId = $link.data('counter');

			if (xhr == null)
			{
				$items = $link.closest('.discussionListItem').andSelf().toggleClass('unread');

				xhr = XenForo.ajax($link.attr('href'), { _xfConfirm: 1 }, function(ajaxData, textStatus)
				{
					xhr = null;

					if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}

					if (typeof ajaxData.unread != 'undefined')
					{
						$items[(ajaxData.unread ? 'addClass' : 'removeClass')]('unread');
					}

					if (counterId && typeof ajaxData.counterFormatted != 'undefined')
					{
						var $counter = $(counterId),
							$total = $counter.find('span.Total');

						if ($total.length)
						{
							$total.text(ajaxData.counterFormatted);
						}
						else
						{
							$counter.text(ajaxData.counterFormatted);
						}
					}

					if (typeof ajaxData.actionPhrase != 'undefined')
					{
						if ($link.text() != '')
						{
							$link.html(ajaxData.actionPhrase);
						}
						if ($link.attr('title'))
						{
							$link.attr('title', ajaxData.actionPhrase);
						}
					}

					XenForo.alert(ajaxData._redirectMessage, '', 1000);
				});
			}
		});
	};

	// *********************************************************************

	XenForo.Notices = function($notices)
	{
		var useCookie = XenForo.visitor.user_id ? false : true,
			cookieName = 'notice_dismiss',
			useScroller = $notices.hasClass('PanelScroller');

		var getCookieIds = function()
		{
			var cookieValue = $.getCookie(cookieName),
				cookieDismissed = cookieValue ? cookieValue.split(',') : [],
				values = [],
				id;

			for (var i = 0; i < cookieDismissed.length; i++)
			{
				id = parseInt(cookieDismissed[i], 10);
				if (id)
				{
					values.push(id);
				}
			}

			return values;
		};
		var dismissed = getCookieIds();

		if (useCookie)
		{
			$notices.find('.Notice').each(function()
			{
				var $notice = $(this),
					id = parseInt($notice.data('notice'), 10);

				if (id && $.inArray(id, dismissed) != -1)
				{
					$notice.remove();
					$('#n' + id).remove();
				}
			});
		}

		var setNoticeVisibility = function($notices, useScroller)
		{
			if (useScroller)
			{
				$notices.find('.Notice').each(function()
				{
					var $notice = $(this),
						display = $(this).css('display'),
						id = parseInt($notice.data('notice'), 10);

					if (display == 'none')
					{
						$notice.remove();
						$('#n' + id).remove();
					}
				});

				var $navLinks = $notices.find('.Nav a');
				if (!$navLinks.filter('.current').length)
				{
					$navLinks.first().addClass('current');
				}
			}

			if (!$notices.find('.Notice').length)
			{
				$notices.remove();
				return;
			}
		};

		setNoticeVisibility($notices, useScroller);

		$notices.show();

		var PanelScroller;

		if (useScroller)
		{
			PanelScroller = XenForo.PanelScroller($notices.find('.PanelContainer'), {
				scrollable: {
					speed: $notices.dataOrDefault('speed', 400) * XenForo._animationSpeedMultiplier,
					vertical: XenForo.isPositive($notices.data('vertical')),
					keyboard: false,
					touch: false,
					prev: '.NoticePrev',
					next: '.NoticeNext'
				},
				autoscroll: { interval: $notices.dataOrDefault('interval', 2000) }
			});

			if (PanelScroller && PanelScroller.getItems().length > 1)
			{
				$(document).bind({
					XenForoWindowBlur: function(e) { PanelScroller.stop(); },
					XenForoWindowFocus: function(e) { PanelScroller.play(); }
				});
			}
		}

		if ($notices.hasClass('FloatingContainer'))
		{
			var $floatingNotices = $notices.find('.Notice');

			$floatingNotices.each(function()
			{
				var $self = $(this),
					displayDuration = $self.data('display-duration'),
					delayDuration = $self.data('delay-duration'),
					autoDismiss = XenForo.isPositive($self.data('auto-dismiss'));

				if (delayDuration)
				{
					setTimeout(function()
					{
						$self.xfFadeDown(XenForo.speed.normal, function()
						{
							if (displayDuration)
							{
								setTimeout(function()
								{
									$self.xfFadeUp(XenForo.speed.normal);

									if (autoDismiss)
									{
										$self.find('a.DismissCtrl').trigger('click');
									}
								}, displayDuration);
							}
						});
					}, delayDuration);
				}
				else
				{
					$self.css('display', 'block');
					if (displayDuration)
					{
						setTimeout(function()
						{
							$self.xfFadeUp(XenForo.speed.normal);

							if (autoDismiss)
							{
								$self.find('a.DismissCtrl').trigger('click');
							}
						}, displayDuration);
					}
				}


			});
		}

		$notices.delegate('a.DismissCtrl', 'click', function(e)
		{
			e.preventDefault();

			var $ctrl = $(this),
				$notice = $ctrl.closest('.Notice'),
				$noticeParent = $notice.closest('.Notices');

			if ($ctrl.data('tooltip'))
			{
				$ctrl.data('tooltip').hide();
			}

			if (PanelScroller)
			{
				PanelScroller.removeItem($notice);

				if (!PanelScroller.getItems().length)
				{
					$notices.xfFadeUp();
				}
			}
			else
			{
				$notice.xfFadeUp(XenForo.speed.fast, function() {
					$notice.remove();
					if (!$noticeParent.find('.Notice').length)
					{
						$notices.xfFadeUp();
					}
				});
			}

			if (useCookie)
			{
				var noticeId = parseInt($notice.data('notice'), 10),
					dismissed = getCookieIds();

				if (noticeId && $.inArray(noticeId, dismissed) == -1)
				{
					dismissed.push(noticeId);
					dismissed.sort(function(a, b) { return (a - b); });

					$.setCookie(cookieName, dismissed.join(','));
				}
			}
			else if (!$ctrl.data('xhr'))
			{
				$ctrl.data('xhr', XenForo.ajax($ctrl.attr('href'), { _xfConfirm: 1 }, function(ajaxData, textStatus)
				{
					$ctrl.removeData('xhr');

					if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}

					//XenForo.alert(ajaxData._redirectMessage, '', 2000);
				}));
			}
		});

		$(window).on('resize', function(e)
		{
			setNoticeVisibility($notices, useScroller);
		});
	};

	// *********************************************************************

	XenForo.PanelScroller = function($container, options)
	{
		var $items = $container.find('.Panels > *');

		// don't initialize if we have just a single panel
		if ($items.length < 2)
		{
			$container.find('.Panels').css('position', 'static');
			return false;
		}

		$items.find('script').remove(); // script should already have been run and document.writes break stuff

		function resizeItems()
		{
			var maxHeight = 0;

			$container.find('.Panels > *').css({ width: $container.innerWidth(), height: 'auto' }).each(function()
			{
				maxHeight = Math.max($(this).outerHeight(), maxHeight);

			}).andSelf().css('height', maxHeight);

			var api = $container.data('scrollable');
			if (api)
			{
				api.seekTo(api.getIndex(), 0);
			}
		};

		options = $.extend(true,
		{
			scrollable:
			{
				circular: true,
				items: '.Panels'
			},
			navigator:
			{
				navi: '.Nav',
				naviItem: 'a',
				activeClass: 'current'
			},
			autoscroll:
			{
				interval: 3000
			}

		}, options);

		$container.css('overflow', 'hidden');

		if (!options.scrollable.vertical)
		{
			$container.css('height', 'auto')
				.find('.Panels').css('width', '20000em')
				.find('.panel').css('float', (XenForo.isRTL() ? 'right' : 'left'));
		}

		$(window).bind('load resize', resizeItems);
		$('.mainContent').bind('XenForoResize', resizeItems);

		resizeItems();

		$container.scrollable(options.scrollable).navigator(options.navigator);

		if ($items.length > 1)
		{
			$container.autoscroll(options.autoscroll);
		}

		return $container.data('scrollable');
	};

	// *********************************************************************

	XenForo.DisplayIgnoredContent = function(e)
	{
		var i, j, styleSheet, rules, rule;

		e.preventDefault();

		$('a.DisplayIgnoredContent').hide();

		// remove the styling that hides quotes etc.
		$('#ignoredUserCss').empty().remove();

		if (document.styleSheets)
		{
			for (i = 0; i < document.styleSheets.length; i++)
			{
				styleSheet = document.styleSheets[i];
				try
				{
					rules = (styleSheet.cssRules ? styleSheet.cssRules : styleSheet.rules);
				}
				catch (e)
				{
					rules = false;
				}
				if (!rules)
				{
					continue;
				}
				for (j = 0; j < rules.length; j++)
				{
					rule = rules[j];

					if (rule.selectorText && rule.selectorText.toLowerCase() == '.ignored')
					{
						if (styleSheet.deleteRule)
						{
							styleSheet.deleteRule(j);
						}
						else
						{
							styleSheet.removeRule(j);
						}
					}
				}
			}
		}

		$('.ignored').removeClass('ignored');
	};

	if ($('html').hasClass('Public'))
	{
		$(function()
		{
			$('body').delegate('a.DisplayIgnoredContent', 'click', XenForo.DisplayIgnoredContent);

			if (window.location.hash)
			{
				var $jump = $(window.location.hash.replace(/[^\w_#-]/g, ''));
				if ($jump.hasClass('ignored'))
				{
					$jump.removeClass('ignored');
					$jump.get(0).scrollIntoView(true);
				}
			}
		});
	}

	// *********************************************************************

	XenForo.SpoilerBBCode = function($spoiler)
	{
		$spoiler.click(function(e)
		{
			$spoiler.siblings(':first').css('fontSize', '25pt');
			return false;
		});
		
		/*$spoiler.click(function(e)
		{
			$spoiler.html($spoiler.data('spoiler')).removeClass('Spoiler').addClass('bbCodeSpoiler');
		});*/
	};

	// *********************************************************************

	/**
	 * Produces centered, square crops of thumbnails identified by data-thumb-selector within the $container.
	 * Requires that CSS of this kind is in place:
	 * .SquareThumb
	 * {
	 * 		position: relative; display: block; overflow: hidden;
	 * 		width: {$thumbHeight}px; height: {$thumbHeight}px;
	 * }
	 * .SquareThumb img
	 * {
	 * 		position: relative; display: block;
	 * }
	 *
	 * @param jQuery $container
	 */
	XenForo.SquareThumbs = function($container)
	{
		var thumbHeight = $container.data('thumb-height') || 44,
			thumbSelector = $container.data('thumb-selector') || 'a.SquareThumb';

		console.info('XenForo.SquareThumbs: %o', $container);

		var $imgs = $container.find(thumbSelector).addClass('SquareThumb').children('img');

		var thumbProcessor = function()
		{
			var $thumb = $(this),
				w = $thumb.width(),
				h = $thumb.height();

			if (!w || !h)
			{
				return;
			}

			if (h > w)
			{
				$thumb.css('width', thumbHeight);
				$thumb.css('top', ($thumb.height() - thumbHeight) / 2 * -1);
			}
			else
			{
				$thumb.css('height', thumbHeight);
				$thumb.css('left', ($thumb.width() - thumbHeight) / 2 * -1);
			}
		};

		$imgs.load(thumbProcessor);
		$imgs.each(thumbProcessor);
	};

	// *********************************************************************

	// Register overlay-loading controls
	// TODO: when we have a global click handler, change this to use rel="Overlay" instead of class="OverlayTrigger"
	XenForo.register(
		'a.OverlayTrigger, input.OverlayTrigger, button.OverlayTrigger, label.OverlayTrigger, a.username, a.avatar',
		'XenForo.OverlayTrigger'
	);

	XenForo.register('.ToggleTrigger', 'XenForo.ToggleTrigger');

	if (!XenForo.isTouchBrowser())
	{
		// Register tooltip elements for desktop browsers
		XenForo.register('.Tooltip', 'XenForo.Tooltip');
		XenForo.register('a.StatusTooltip', 'XenForo.StatusTooltip');
		XenForo.register('.PreviewTooltip', 'XenForo.PreviewTooltip');
	}

	XenForo.register('a.LbTrigger', 'XenForo.LightBoxTrigger');

	// Register click-proxy controls
	XenForo.register('.ClickProxy', 'XenForo.ClickProxy');

	// Register popup menu controls
	XenForo.register('.Popup', 'XenForo.PopupMenu', 'XenForoActivatePopups');

	// Register scrolly pagenav elements
	XenForo.register('.PageNav', 'XenForo.PageNav');

	// Register tabs
	XenForo.register('.Tabs', 'XenForo.Tabs');

	// Register square thumb cropper
	XenForo.register('.SquareThumbs', 'XenForo.SquareThumbs');

	// Handle all xenForms
	XenForo.register('form.xenForm, .MultiSubmitFix', 'XenForo.MultiSubmitFix');

	// Register check-all controls
	XenForo.register('input.CheckAll, a.CheckAll, label.CheckAll', 'XenForo.CheckAll');

	// Register auto-checker controls
	XenForo.register('input.AutoChecker', 'XenForo.AutoChecker');

	// Register toggle buttons
	XenForo.register('label.ToggleButton', 'XenForo.ToggleButton');

	// Register auto inline uploader controls
	XenForo.register('form.AutoInlineUploader', 'XenForo.AutoInlineUploader');

	// Register form auto validators
	XenForo.register('form.AutoValidator', 'XenForo.AutoValidator');

	// Register auto time zone selector
	XenForo.register('select.AutoTimeZone', 'XenForo.AutoTimeZone');

	// Register generic content loader
	XenForo.register('a.Loader, input.Loader', 'XenForo.Loader');

	var supportsStep = 'step' in document.createElement('input');

	// Register form controls
	XenForo.register('input, textarea', function(i)
	{
		var $this = $(this);

		switch ($this.attr('type'))
		{
			case 'hidden':
			case 'submit':
				return;
			case 'checkbox':
			case 'radio':
				// Register auto submitters
				if ($this.hasClass('SubmitOnChange'))
				{
					XenForo.create('XenForo.SubmitOnChange', this);
				}
				return;
		}

		// Spinbox / input[type=number]
		if ($this.attr('type') == 'number' && supportsStep)
		{
			// use the XenForo implementation instead, as browser implementations seem to be universally horrible
			this.type = 'text';
			$this.addClass('SpinBox number');
		}
		if ($this.hasClass('SpinBox'))
		{
			XenForo.create('XenForo.SpinBox', this);
		}

		// Prompt / placeholder
		if ($this.hasClass('Prompt'))
		{
			console.error('input.Prompt[title] is now deprecated. Please replace any instances with input[placeholder] and remove the Prompt class.');
			$this.attr({ placeholder: $this.attr('title'), title: '' });
		}
		if ($this.attr('placeholder'))
		{
			XenForo.create('XenForo.Prompt', this);
		}

		// LiveTitle
		if ($this.data('livetitletemplate'))
		{
			XenForo.create('XenForo.LiveTitle', this);
		}

		// DatePicker
		if ($this.is(':date'))
		{
			XenForo.create('XenForo.DatePicker', this);
		}

		// AutoComplete
		if ($this.hasClass('AutoComplete'))
		{
			XenForo.create('XenForo.AutoComplete', this);
		}

		// UserTagger
		if ($this.hasClass('UserTagger'))
		{
			XenForo.create('XenForo.UserTagger', this);
		}

		// AutoSelect
		if ($this.hasClass('AutoSelect'))
		{
			XenForo.create('XenForo.AutoSelect', this);
		}

		// AutoValidator
		if (XenForo.isAutoValidatorField(this))
		{
			XenForo.create('XenForo.AutoValidatorControl', this);
		}

		if ($this.is('textarea.StatusEditor'))
		{
			XenForo.create('XenForo.StatusEditor', this);
		}

		// Register Elastic textareas
		if ($this.is('textarea.Elastic'))
		{
			XenForo.create('XenForo.TextareaElastic', this);
		}
	});

	// Register form previewer
	XenForo.register('form.Preview', 'XenForo.PreviewForm');

	// Register field adder
	XenForo.register('a.FieldAdder, input.FieldAdder', 'XenForo.FieldAdder');

	// Read status toggler
	XenForo.register('a.ReadToggle', 'XenForo.ReadToggle');

	/**
	 * Public-only registrations
	 */
	if ($('html').hasClass('Public'))
	{
		// Register the login bar handle
		XenForo.register('#loginBar', 'XenForo.LoginBar');

		// Register the header search box
		XenForo.register('#QuickSearch', 'XenForo.QuickSearch');

		// Register attribution links
		XenForo.register('a.AttributionLink', 'XenForo.AttributionLink');

		// CAPTCHAS
		XenForo.register('#ReCaptcha', 'XenForo.ReCaptcha');
		XenForo.register('.NoCaptcha', 'XenForo.NoCaptcha');
		XenForo.register('#SolveMediaCaptcha', 'XenForo.SolveMediaCaptcha');
		XenForo.register('#KeyCaptcha', 'XenForo.KeyCaptcha');
		XenForo.register('#Captcha', 'XenForo.Captcha');

		// Resize large BB code images
		XenForo.register('img.bbCodeImage', 'XenForo.BbCodeImage');

		// Handle like/unlike links
		XenForo.register('a.LikeLink', 'XenForo.LikeLink');

		// Register node description tooltips
		if (!XenForo.isTouchBrowser())
		{
			XenForo.register('h3.nodeTitle a', 'XenForo.NodeDescriptionTooltip');
		}

		// Register visitor menu
		XenForo.register('#AccountMenu', 'XenForo.AccountMenu');

		// Register follow / unfollow links
		XenForo.register('a.FollowLink', 'XenForo.FollowLink');

		XenForo.register('li.PopupItemLink', 'XenForo.PopupItemLink');

		// Register notices
		XenForo.register('.Notices', 'XenForo.Notices');

		// Spoiler BB Code
		XenForo.register('button.Spoiler', 'XenForo.SpoilerBBCode');
	}

	// Register control disablers last so they disable anything added by other behaviours
	XenForo.register('input:checkbox.Disabler, input:radio.Disabler', 'XenForo.Disabler');

	// *********************************************************************
	var isScrolled = false;
	$(window).on('load', function() {
		if (isScrolled || !window.location.hash)
		{
			return;
		}

		var hash = window.location.hash.replace(/[^a-zA-Z0-9_-]/g, ''),
			$match = hash ? $('#' + hash) : $();

		if ($match.length)
		{
			$match.get(0).scrollIntoView(true);
		}
	});

	/**
	 * Use jQuery to initialize the system
	 */
	$(function()
	{
		XenForo.init();

		if (window.location.hash)
		{
			// do this after the document is ready as triggering it too early
			// causes the initial hash to trigger a scroll
			$(window).one('scroll', function(e) {
				isScrolled = true;
			});
		}
	});
}
(jQuery, this, document);
