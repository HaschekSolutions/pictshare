Dropzone.autoDiscover = false;
hljs.initHighlightingOnLoad();

document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("dropzone") != null) {
        var myDropzone = new Dropzone("#dropzone");
        if (typeof maxUploadFileSize !== "undefined")
            myDropzone.options.maxFilesize = maxUploadFileSize;

        myDropzone.options.timeout = 0;

        myDropzone.on("sending", function (file, xhr, formData) {
            var uploadCodeElem = document.getElementById("uploadcode");
            if (uploadCodeElem)
                formData.append("uploadcode", uploadCodeElem.value);
        });

        myDropzone.on('error', function (file, response) {
            var uploadInfo = document.getElementById("uploadinfo");
            if (response == null || response == "null") {
                uploadInfo.insertAdjacentHTML("beforeend",
                    renderMessage(
                        "Error uploading " + file.name,
                        "Reason is unknown :(",
                        "danger"
                    )
                );
            } else {
                if (response.status == 'err') {
                    uploadInfo.insertAdjacentHTML("beforeend",
                        renderMessage(
                            "Error uploading " + file.name,
                            "Reason: " + response.reason,
                            "danger"
                        )
                    );
                } else {
                    uploadInfo.insertAdjacentHTML("beforeend",
                        renderMessage(
                            "Error uploading " + file.name,
                            "Reason: " + response,
                            "danger"
                        )
                    );
                }
            }
        });

        myDropzone.on("success", function (file, response) {
            console.log("raw response: " + response);
            var uploadInfo = document.getElementById("uploadinfo");
            if (response == null || response == "null") {
                uploadInfo.insertAdjacentHTML("beforeend",
                    renderMessage(
                        "Error uploading " + file.name,
                        "Reason is unknown :(",
                        "danger"
                    )
                );
            } else {
                if (response.status == 'ok') {
                    uploadInfo.insertAdjacentHTML("beforeend",
                        renderMessage(file.name + " uploaded as <a target='_blank' href='/" + response.hash + "'>" + response.hash + "</a>", "URL: <a target='_blank' href='" + response.url + "'>" + response.url + "</a> <button class='btn btn-primary btn-sm' onClick='navigator.clipboard.writeText(\"" + response.url + "\");'>Copy URL</button>", "success")
                    );
                } else if (response.status == 'err') {
                    uploadInfo.insertAdjacentHTML("beforeend",
                        renderMessage(
                            "Error uploading " + file.name,
                            response.reason,
                            "danger"
                        )
                    );
                }
            }
        });

        document.onpaste = function (event) {
            var items = (event.clipboardData || event.originalEvent.clipboardData).items;
            for (var index in items) {
                var item = items[index];
                if (item.kind === 'file') {
                    myDropzone.addFile(item.getAsFile());
                }
            }
        };
    }
});

function renderMessage(title, message, type) {
    if (!type)
        type = "danger";
    return `<div class='alert alert-${type}' role='alert'><strong>${title}</strong><br/>${message}</div>`;
}