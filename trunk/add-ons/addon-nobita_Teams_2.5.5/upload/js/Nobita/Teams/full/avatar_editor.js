! function(e, t, n, r) {
    XenForo.AvatarEditor = function(e) {
        this.__construct(e)
    };
    XenForo.AvatarEditor.prototype = {
        __construct: function(t) {
            this.$form = t.bind({
                submit: e.context(this, "saveChanges"),
                AutoInlineUploadComplete: e.context(this, "uploadComplete")
            })
        },
        uploadComplete: function(e) {
            this.updateEditor(e.ajaxData)
        },
        updateEditor: function(t) {
            e(".previewAvatar").attr("src", t.url);
            e("input[name=team_avatar_date]").val(t.team_avatar_date);

            e(".teamLogo").find("img").attr("src", t.url);

            if (parseInt(t.team_avatar_date, 10)) {
                e("#logoControl").show();
                e("input[name=delete]").removeAttr("checked")
            } else {
                e("#logoControl").hide();
            }
        },
        saveChanges: function(t) {
            if (this.$form.find("input[name=_xfUploader]").length) {
                return true;
            }
            t.preventDefault();
            XenForo.ajax(this.$form.attr("action"), this.$form.serializeArray(), e.context(this, "saveChangesSuccess"));
        },
        saveChangesSuccess: function(e, t) {
            if (XenForo.hasResponseError(e)) {
                return false
            }
            this.updateEditor(e);
            if (e.redirectUri) {
                location.href = e.redirectUri
            }
        }
    };
    XenForo.register(".TeamAvatarEditor", "XenForo.AvatarEditor")
}(jQuery, this, document)
