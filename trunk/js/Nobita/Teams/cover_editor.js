!function(o,r,e){e.__groupCropData=null,e.GroupCoverContainer=function(n){o(window).on("resize orientationchange",function(){o(r).triggerHandler("renderCoverComponents")}),o(r).on("ready renderCoverComponents",function(){var o,r=n.find("img.coverPhoto"),t=parseInt(n.width());o=t>=1024?1:t/1024;var a=350*o;n.height(a),r.cropbox({width:t,height:a,showControls:"never"}).on("cropbox",function(o,r){e.__groupCropData=r,e.__groupCropData.containerW=t})})},e.GroupSubmitCropHandle=function(o){o.on("click",function(r){r.preventDefault();var n=o.data("save");return o.attr("disabled","disabled").addClass("disabled"),n&&e.__groupCropData?void e.ajax(n,e.__groupCropData,function(o){return e.__groupCropData=null,e.hasResponseError(o)?!1:void(o._redirectTarget&&(window.location.href=o._redirectTarget))}):!1})},e.register(".coverReposition","XenForo.GroupCoverContainer"),e.register(".groupSubmitCropper","XenForo.GroupSubmitCropHandle")}(jQuery,document,XenForo);