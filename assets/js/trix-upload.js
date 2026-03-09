document.addEventListener('trix-attachment-add', function (event) {
    var attachment = event.attachment;
    if (attachment.file) {
        uploadFile(attachment);
    }
});

function uploadFile(attachment) {
    var file = attachment.file;
    var formData = new FormData();
    formData.append('upload', file);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/upload-image', true);

    xhr.upload.addEventListener('progress', function (event) {
        if (event.lengthComputable) {
            var progress = event.loaded / event.total * 100;
            attachment.setUploadProgress(progress);
        }
    });

    xhr.addEventListener('load', function () {
        if (xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            attachment.setAttributes({ url: data.url, href: data.url });
        }
    });

    xhr.send(formData);
}
