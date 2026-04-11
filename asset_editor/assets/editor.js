/* global ace */
(function () {
    'use strict';

    if (typeof ace === 'undefined') {
        return;
    }

    var wrappers = document.querySelectorAll('.asset-editor-wrap');
    Array.prototype.forEach.call(wrappers, function (wrap) {
        var textarea = wrap.querySelector('.js-ace-editor');
        if (!textarea) {
            return;
        }
        var mode = wrap.getAttribute('data-ace-mode') || 'text';

        var host = document.createElement('div');
        host.className = 'asset-editor-ace';
        wrap.insertBefore(host, textarea);
        textarea.style.display = 'none';

        var editor = ace.edit(host);
        editor.setTheme('ace/theme/chrome');
        editor.session.setMode('ace/mode/' + mode);
        editor.session.setTabSize(4);
        editor.session.setUseSoftTabs(true);
        editor.setShowPrintMargin(false);
        editor.setValue(textarea.value, -1);

        editor.session.on('change', function () {
            textarea.value = editor.getValue();
        });

        // Keep the form submittable from inside the editor (Ctrl/Cmd+S).
        editor.commands.addCommand({
            name: 'save',
            bindKey: { win: 'Ctrl-S', mac: 'Cmd-S' },
            exec: function () {
                var form = textarea.form;
                if (form) {
                    form.submit();
                }
            }
        });
    });
})();
