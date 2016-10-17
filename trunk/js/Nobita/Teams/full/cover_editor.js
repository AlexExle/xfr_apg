!function($, document, XenForo)
{
    XenForo.__groupCropData = null;

    XenForo.GroupCoverContainer = function($container)
    {
        $(window).on('resize orientationchange', function(e)
        {
            $(document).triggerHandler('renderCoverComponents');
        });

        $(document).on('ready renderCoverComponents', function(e)
        {
            var $image = $container.find('img.coverPhoto'),
                width = parseInt($container.width()),
                ratio;

            if (width >= 1024)
            {
                ratio = 1;
            }
            else
            {
                ratio = width / 1024;
            }

            var height = ratio * 350;
            $container.height(height);

            $image.cropbox(
            {
                width: width,
                height: height,
                showControls: 'never'
            })
            .on('cropbox', function(event, data)
            {
                XenForo.__groupCropData = data;
                XenForo.__groupCropData['containerW'] = width;
            });
        });
    };

    XenForo.GroupSubmitCropHandle = function($button)
    {
        $button.on('click', function(e)
        {
            e.preventDefault();

            var saveUrl = $button.data('save');
            $button.attr('disabled', 'disabled').addClass('disabled');

            if (!saveUrl || !XenForo.__groupCropData)
            {
                return false;
            }

            XenForo.ajax(saveUrl, XenForo.__groupCropData, function(ajaxData, textStatus)
            {
                XenForo.__groupCropData = null;

                if (XenForo.hasResponseError(ajaxData))
                {
                    return false;
                }

                if (ajaxData._redirectTarget)
                {
                    window.location.href = ajaxData._redirectTarget;
                }
            });
        });
    };

    XenForo.register('.coverReposition', 'XenForo.GroupCoverContainer');
    XenForo.register('.groupSubmitCropper', 'XenForo.GroupSubmitCropHandle');
}
(jQuery, document, XenForo);
