// Script padrão pra inicializar o editor html do tinymce
tinymce.init({
    selector: '.editor',  // change this value according to your HTML
    plugins : 'advlist autolink link image imagetools lists advlist charmap media preview autoresize hr jbimages textcolor fullscreen table help paste spellchecker',
    height: 300,
    language: 'pt_BR',
    entity_encoding : "raw",
    menubar: false,
    toolbar: ['undo redo | bold italic underline strikethrough | bullist numlist | alinhamento | forecolor backcolor | table | link | fontselect fontsizeselect | image media | preview | hr | spellchecker | fullscreen',
    ],
    audio_template_callback: function(data) {
        return '<audio controls>' + '\n<source src="' + data.source1 + '"' + (data.source1mime ? ' type="' + data.source1mime + '"' : '') + ' />\n' + '</audio>';
    },
    setup: function(editor) {
        editor.addButton('alinhamento', {
            type: 'listbox',
            text: 'Alinhar',
            icon: false,
            onselect: function(e) {
                tinyMCE.execCommand(this.value());
            },
            values: [
                {icon: 'alignleft', value: 'JustifyLeft'},
                {icon: 'alignright', value: 'JustifyRight'},
                {icon: 'aligncenter', value: 'JustifyCenter'},
                {icon: 'alignjustify', value: 'JustifyFull'},
                {icon: 'outdent', value: 'outdent'},
                {icon: 'indent', value: 'indent'},
            ],
            onPostRender: function() {
                // Select the firts item by default
                this.value('JustifyLeft');
            }
        });
    },
    browser_spellcheck: true,
    contextmenu: false,
    table_default_styles: {
        width: '75%'
    }
});
