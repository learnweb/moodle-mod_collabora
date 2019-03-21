define([], function() {
    var courseURL;
    var closeWindow;

    /**
     * @param {Event} e
     */
    function checkMessage(e) {
        var msg, msgId;
        try {
            msg = JSON.parse(e.data);
            msgId = msg.MessageId;
        } catch (exc) {
            msgId = e.data;
        }
        if (msgId === 'UI_Close') {
            if (closeWindow) {
                window.close();
            } else {
                window.location.href = courseURL;
            }
        }
    }

    return {
        init: function(opts) {
            courseURL = opts.courseurl;
            closeWindow = opts.closewindow;
            window.addEventListener('message', checkMessage);
        }
    };
});
