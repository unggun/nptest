window.xtSaveHiddenData = function(mapperId, rowId, field, value, empty) {
    if (typeof ace !== 'undefined') {
        value = editor.getSession().getValue();
    }
    if (empty) {
        value = "";
    }
    inputName = mapperId + '[' + rowId + '][' + field + ']';
    if ($(inputName)) {
        $(inputName).value = value;
    } else {
        $(mapperId + '_additional_config').insert({'after': '<input type="hidden" id="' + inputName + '" name="' + inputName + '" value="' + quoteAttribute(value) + '"/>'});
    }
    Windows.closeAll();
};

function quoteAttribute(s, preserveCR) {
    preserveCR = preserveCR ? '&#13;' : '\n';
    return ('' + s)/* Forces the conversion to string. */
        .replace(/&/g, '&amp;')/* This MUST be the 1st replacement. */
        .replace(/'/g, '&apos;')/* The 4 other predefined entities, required. */
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        /*
         You may add other replacements here for HTML only
         (but it's not necessary).
         Or for XML, only if the named entities are defined in its DTD.
         */
        .replace(/\r\n/g, preserveCR)/* Must be before the next replacement. */
        .replace(/[\r\n]/g, preserveCR);
}