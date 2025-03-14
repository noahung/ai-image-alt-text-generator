jQuery(document).ready(function($) {
    var selectedImages = [];

    // Ensure loading message is hidden on page load
    $('#biatg-loading').hide();

    // Open media uploader
    $('#biatg-select-images').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select Images',
            button: {
                text: 'Use Selected Images'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            selectedImages = attachments.map(function(attachment) {
                return {
                    id: attachment.id,
                    url: attachment.url
                };
            });

            // Display selected images
            var previewHtml = '';
            $.each(selectedImages, function(i, image) {
                previewHtml += '<img src="' + image.url + '" style="max-width: 100px; height: auto; margin: 5px;" />';
            });
            $('#biatg-image-preview').html(previewHtml);
            $('#biatg-selected-images').show();

            // Update image count
            $('#biatg-image-count').text('(' + selectedImages.length + ')');

            // Enable the preview button
            $('#biatg-preview').prop('disabled', false);
        });

        frame.open();
    });

    // Preview alt texts
    $('#biatg-preview').on('click', function() {
        if (selectedImages.length === 0) {
            alert('Please select some images first.');
            return;
        }

        // Show loading message next to the button
        console.log('Showing loading message next to Preview Alt Texts button...');
        $('#biatg-loading').show();
        $('#biatg-error').hide();
        $('#biatg-results').hide();
        $('#biatg-save').hide();

        var image_ids = selectedImages.map(function(image) {
            return image.id;
        });

        console.log('Starting AJAX call with image IDs:', image_ids);

        // Make the AJAX call
        $.ajax({
            url: biatg_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'biatg_generate_alt_texts',
                nonce: biatg_ajax.nonce,
                image_ids: image_ids
            },
            beforeSend: function() {
                console.log('AJAX request started...');
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                $('#biatg-loading').hide();
                if (response.success) {
                    var html = '';
                    $.each(response.data, function(i, item) {
                        html += '<tr>' +
                            '<td><img src="' + item.url + '" style="max-width: 100px; height: auto;"></td>' +
                            '<td>' + (item.current_alt || 'None') + '</td>' +
                            '<td><input type="text" class="biatg-alt-input" data-id="' + item.id + '" value="' + item.generated_alt + '"></td>' +
                            '</tr>';
                    });
                    $('#biatg-table-body').html(html);
                    $('#biatg-results').show();
                    $('#biatg-save').show();
                } else {
                    $('#biatg-error').text(response.data).show();
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                $('#biatg-loading').hide();
                $('#biatg-error').text('AJAX Error: ' + error + ' - ' + xhr.responseText).show();
            },
            complete: function() {
                console.log('AJAX call completed.');
                $('#biatg-loading').hide();
            }
        });
    });

    // Save alt texts
    $('#biatg-save').on('click', function() {
        var alt_texts = [];
        $('.biatg-alt-input').each(function() {
            alt_texts.push({
                id: $(this).data('id'),
                alt_text: $(this).val()
            });
        });

        $('#biatg-loading').show();
        $('#biatg-error').hide();

        $.ajax({
            url: biatg_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'biatg_save_alt_texts',
                nonce: biatg_ajax.nonce,
                alt_texts: alt_texts
            },
            success: function(response) {
                $('#biatg-loading').hide();
                if (response.success) {
                    alert('Alt texts saved successfully!');
                } else {
                    $('#biatg-error').text(response.data).show();
                }
            },
            error: function(xhr, status, error) {
                $('#biatg-loading').hide();
                $('#biatg-error').text('AJAX Error: ' + error + ' - ' + xhr.responseText).show();
            }
        });
    });
});
