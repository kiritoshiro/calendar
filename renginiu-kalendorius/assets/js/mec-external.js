// TinyMce Plugins
if(jQuery('.mec-fes-form').length < 1)
{
    var items = '';
    if(typeof mec_admin_localize !== "undefined") items = JSON.parse(mec_admin_localize.mce_items);

    var menu = [];
    if(items && typeof tinymce !== 'undefined')
    {
        tinymce.PluginManager.add('mec_mce_buttons', function(editor, url)
        {
            items.shortcodes.forEach(function(e, i)
            {
                menu.push(
                {
                    text: items.shortcodes[i]['PN'].replace(/-/g, ' '),
                    id: items.shortcodes[i]['ID'],
                    classes: 'mec-mce-items',
                    onselect: function(e)
                    {
                        editor.insertContent(`[MEC id="${e.control.settings.id}"]`);
                    }
                });
            });

            // Add menu button
            editor.addButton('mec_mce_buttons',
            {
                text: items.mce_title,
                icon: false,
                type: 'menubutton',
                menu: menu,
                onPostRender: function() {
                    const titleText = items.mce_title.trim();

                    const interval = setInterval(() => {
                        const buttons = document.querySelectorAll('.mce-menubtn');

                        buttons.forEach(btn => {
                            const span = btn.querySelector('.mce-txt');
                            if (span && span.textContent.trim() === titleText) {
                                if (!btn.classList.contains('mce-fixed-width')) {
                                    btn.classList.add('mce-fixed-width', 'mce-listbox');
                                }
                            }
                        });
                    });
                }
            });
        });
    }
}