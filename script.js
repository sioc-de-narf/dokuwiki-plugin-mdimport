function addBtnActionMdimport($btn, props, edid) {
    $btn.click(function() {
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.md,.txt,text/markdown,text/plain';
        fileInput.style.display = 'none';

        fileInput.onchange = function(event) {
            var file = event.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function(e) {
                var content = e.target.result;
                var sectok = jQuery('#dw__editform input[name="sectok"]').val()
                          || jQuery('input[name="sectok"]').first().val()
                          || '';

                fetch(DOKU_BASE + 'lib/exe/ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    credentials: 'same-origin',
                    body: 'call=plugin_mdimport'
                        + '&sectok=' + encodeURIComponent(sectok)
                        + '&content=' + encodeURIComponent(content)
                })
                .then(response => {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(convertedContent => {
                    insertAtCarret(edid, convertedContent);
                })
                .catch(error => {
                    console.error('Conversion error:', error);
                    alert('Error converting file.');
                });
            };
            reader.readAsText(file);
        };

        document.body.appendChild(fileInput);
        fileInput.click();
        document.body.removeChild(fileInput);
        return false;
    });
    return 'mdimport';
}
