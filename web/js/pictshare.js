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
                    if (window.PictShareUploads) {
                        window.PictShareUploads.add({
                            hash:        response.hash,
                            url:         response.url,
                            delete_code: response.delete_code,
                            delete_url:  response.delete_url,
                            kind:        'file',
                            filetype:    response.filetype,
                            name:        file.name,
                            size:        file.size
                        });
                        if (typeof refreshMyUploads === 'function') refreshMyUploads();
                    }
                    uploadInfo.insertAdjacentHTML("beforeend",
                        renderMessage(file.name + " uploaded as <a target='_blank' href='/" + response.hash + "'>" + response.hash + "</a>", "URL: <a target='_blank' href='" + response.url + "'>" + response.url + "</a> <button class='btn btn-primary btn-sm' onClick='navigator.clipboard.writeText(\"" + response.url + "\");'>Copy URL</button> <button id='showqrdtn-" + response.hash + "' class='btn btn-primary btn-sm' onClick='showQrcode(\"" + response.hash + "\", \""+ response.url + "\");'>Show QRCode</button><div id='" + response.hash + "' class='p-1'></div>", "success")
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

        var submitPasteBtn = document.getElementById("submitPaste");
        if (submitPasteBtn) {
            submitPasteBtn.addEventListener("click", function () {
                var text = document.getElementById("pasteText").value;
                var type = document.getElementById("pasteType").value;
                var uploadCode = document.getElementById("uploadcode") ? document.getElementById("uploadcode").value : "";

                if (!text) {
                    alert("Please paste some text first");
                    return;
                }

                submitPasteBtn.disabled = true;
                submitPasteBtn.innerText = "Uploading...";

                var formData = new FormData();
                // Base64 encode the text to use the existing base64 upload API
                // We use a helper to handle UTF-8 correctly
                var base64data = "data:text/plain;base64," + btoa(unescape(encodeURIComponent(text)));
                formData.append("base64", base64data);
                formData.append("uploadcode", uploadCode);
                formData.append("format", type);
                
                fetch("/api/upload", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitPasteBtn.disabled = false;
                    submitPasteBtn.innerText = "Upload Paste";
                    
                    var uploadInfo = document.getElementById("uploadinfo");
                    if (data.status == 'ok') {
                        if (window.PictShareUploads) {
                            window.PictShareUploads.add({
                                hash:        data.hash,
                                url:         data.url,
                                delete_code: data.delete_code,
                                delete_url:  data.delete_url,
                                kind:        'file',
                                filetype:    data.filetype,
                                name:        data.hash,
                                size:        text.length
                            });
                            if (typeof refreshMyUploads === 'function') refreshMyUploads();
                        }
                        // If user wanted MD but got TXT (or vice versa), we might want to rename it,
                        // but the current API logic determines type by content.
                        // For text/markdown, we might need to adjust src/api/upload.php
                        uploadInfo.insertAdjacentHTML("beforeend",
                            renderMessage("Paste uploaded as <a target='_blank' href='/" + data.hash + "'>" + data.hash + "</a>", "URL: <a target='_blank' href='" + data.url + "'>" + data.url + "</a> <button class='btn btn-primary btn-sm' onClick='navigator.clipboard.writeText(\"" + data.url + "\");'>Copy URL</button> <button id='showqrdtn-" + data.hash + "' class='btn btn-primary btn-sm' onClick='showQrcode(\"" + data.hash + "\", \""+ data.url + "\");'>Show QRCode</button><div id='" + data.hash + "' class='p-1'></div>", "success")
                        );
                        document.getElementById("pasteText").value = "";
                    } else {
                        uploadInfo.insertAdjacentHTML("beforeend",
                            renderMessage("Error uploading paste", data.reason || "Unknown error", "danger")
                        );
                    }
                })
                .catch(error => {
                    submitPasteBtn.disabled = false;
                    submitPasteBtn.innerText = "Upload Paste";
                    console.error("Error:", error);
                    alert("An error occurred during upload");
                });
            });
        }
    }
});

function showQrcode(hash, url) {
    showqrbtn = document.getElementById('showqrdtn-' + hash);
    targetDiv = document.getElementById(hash);

    if (targetDiv.hasChildNodes()) {
        targetDiv.innerHTML = '';
        showqrbtn.textContent = "Show QRCode";
    } else {
        new QRCode(targetDiv, url);
        showqrbtn.textContent = "Hide QRCode";
    }
}

function renderMessage(title, message, type) {
    if (!type)
        type = "danger";
    return `<div class='alert alert-${type}' role='alert'><strong>${title}</strong><br/>${message}</div>`;
}