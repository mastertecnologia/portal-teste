/**
 * Compressão de imagens no frontend usando Canvas API.
 * Reduz tamanho antes de enviar ao backend (economiza banda e storage).
 */

/**
 * Comprime uma imagem (File) em JPEG.
 * @param {File} file
 * @param {object} opts
 * @param {number} opts.maxWidth - largura máxima (default 1200)
 * @param {number} opts.maxHeight - altura máxima (default 1200)
 * @param {number} opts.quality - 0..1 (default 0.75)
 * @returns {Promise<{file: File, originalSize: number, newSize: number, width: number, height: number}>}
 */
export async function compressImage(file, opts = {}) {
  const {
    maxWidth = 1200,
    maxHeight = 1200,
    quality = 0.75,
    mimeType = 'image/jpeg',
  } = opts;

  if (!file || !file.type.startsWith('image/')) {
    throw new Error('Arquivo não é uma imagem válida');
  }

  // Lê o arquivo como bitmap
  const dataUrl = await readAsDataURL(file);
  const img = await loadImage(dataUrl);

  // Calcula novas dimensões mantendo proporção
  let { width, height } = img;
  if (width > maxWidth || height > maxHeight) {
    const ratio = Math.min(maxWidth / width, maxHeight / height);
    width = Math.round(width * ratio);
    height = Math.round(height * ratio);
  }

  // Renderiza em canvas
  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0, width, height);

  // Converte para Blob
  const blob = await new Promise((resolve) => {
    canvas.toBlob(resolve, mimeType, quality);
  });

  // Constrói novo File preservando o nome original (mas com extensão correta)
  const baseName = file.name.replace(/\.[^.]+$/, '');
  const ext = mimeType === 'image/jpeg' ? '.jpg' : '.webp';
  const newFile = new File([blob], baseName + ext, { type: mimeType });

  return {
    file: newFile,
    originalSize: file.size,
    newSize: newFile.size,
    width,
    height,
    reduction: ((1 - newFile.size / file.size) * 100).toFixed(1),
  };
}

function readAsDataURL(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Falha ao ler arquivo'));
    reader.readAsDataURL(file);
  });
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Falha ao carregar imagem'));
    img.src = src;
  });
}

/**
 * Formata bytes para string legível (ex: "1.2 MB").
 */
export function humanFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(2) + ' MB';
}
