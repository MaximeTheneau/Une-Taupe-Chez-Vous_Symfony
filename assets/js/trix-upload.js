// CKEditor 4 image upload listener for /admin/upload-image
if (window.CKEDITOR) {
    CKEDITOR.on('instanceCreated', function (event) {
        var editor = event.editor;

        editor.on('fileUploadResponse', function (evt) {
            var data = evt.data;
            var xhr = data.fileLoader.xhr;

            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.uploaded === 1) {
                        data.url = response.url;
                        data.fileName = response.fileName;
                    } else {
                        evt.cancel();
                        data.message = (response.error && response.error.message)
                            ? response.error.message
                            : 'Upload failed';
                    }
                } catch (e) {
                    evt.cancel();
                    data.message = 'Invalid server response';
                }
                evt.stop();
            }
        });
    });
}
