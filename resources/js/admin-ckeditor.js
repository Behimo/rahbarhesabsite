import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

window.ClassicEditor = ClassicEditor;

const TOOLBARS = {
  basic: [
    'heading', '|',
    'bold', 'italic', 'link', '|',
    'bulletedList', 'numberedList', '|',
    'blockQuote', 'insertTable', '|',
    'undo', 'redo',
  ],
  full: [
    'heading', '|',
    'bold', 'italic', 'link', '|',
    'bulletedList', 'numberedList', '|',
    'uploadImage', 'blockQuote', 'insertTable', '|',
    'undo', 'redo',
  ],
};

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function createUploadAdapterPlugin(uploadUrl) {
  class MediaUploadAdapter {
    constructor(loader) {
      this.loader = loader;
    }

    upload() {
      return this.loader.file.then((file) => {
        const data = new FormData();
        data.append('file', file);

        return fetch(uploadUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: data,
        }).then(async (res) => {
          const json = await res.json();
          if (!res.ok || !json.url) {
            throw new Error(json.message || 'آپلود ناموفق بود');
          }
          return { default: json.url };
        });
      });
    }

    abort() {}
  }

  return function MediaUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new MediaUploadAdapter(loader);
  };
}

function insertImage(editor, url) {
  editor.model.change((writer) => {
    const imageElement = writer.createElement('imageBlock', { src: url });
    editor.model.insertContent(imageElement, editor.model.document.selection);
  });
}

async function initEditor(textarea) {
  if (!textarea || textarea.dataset.ckeditorReady === '1') {
    return null;
  }

  const toolbarKey = textarea.dataset.ckeditorToolbar || 'basic';
  const uploadUrl = textarea.dataset.ckeditorUploadUrl || '';
  const mediaBtnId = textarea.dataset.ckeditorMediaBtn || '';
  const rtl = textarea.dataset.ckeditorRtl !== '0';
  const toolbarItems = TOOLBARS[toolbarKey] || TOOLBARS.basic;

  const config = {
    toolbar: {
      items: toolbarItems,
      shouldNotGroupWhenFull: true,
    },
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
    },
  };

  if (toolbarItems.includes('uploadImage')) {
    config.image = {
      toolbar: ['imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'],
    };
    if (uploadUrl) {
      config.extraPlugins = [createUploadAdapterPlugin(uploadUrl)];
    }
  }

  const editor = await ClassicEditor.create(textarea, config);
  textarea.dataset.ckeditorReady = '1';

  if (rtl) {
    editor.editing.view.change((writer) => {
      writer.setAttribute('dir', 'rtl', editor.editing.view.document.getRoot());
    });
  }

  const form = textarea.closest('form');
  form?.addEventListener('submit', () => {
    textarea.value = editor.getData();
  });

  if (mediaBtnId) {
    document.getElementById(mediaBtnId)?.addEventListener('click', () => {
      if (typeof window.RahbarMediaPicker?.open !== 'function') {
        return;
      }
      window.RahbarMediaPicker.open({
        onSelect(url) {
          insertImage(editor, url);
        },
      });
    });
  }

  return editor;
}

async function bootEditors() {
  const nodes = document.querySelectorAll('textarea[data-ckeditor]');
  await Promise.all([...nodes].map((node) => initEditor(node).catch((err) => console.error(err))));
  window.dispatchEvent(new CustomEvent('rahbar:ckeditor-ready'));
}

bootEditors();

window.RahbarCkeditor = {
  init: initEditor,
  boot: bootEditors,
};
