jQuery(document).ready(function($){
    var custom_uploader;
    $('#demo-media').click(function(e) {
        e.preventDefault();
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
        custom_uploader = wp.media({
            title: 'Choose Image',
            // 以下のコメントアウトを解除すると画像のみに限定される。
            library: {
                type: 'image'
            },
            button: {
                text: 'Choose Image'
            },
            multiple: true // falseにすると画像を1つしか選択できなくなる
        });
        custom_uploader.on('select', function() {
            var images = custom_uploader.state().get('selection');
            images.each(function(file){
                $('#demo-images').append('<img src="'+file.toJSON().url+'" />');
            });
        });
        custom_uploader.open();
    });
});

