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

                fetch('lib/plugins/mdimport/convert.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'content=' + encodeURIComponent(content)
                })
                .then(response => response.text())
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
