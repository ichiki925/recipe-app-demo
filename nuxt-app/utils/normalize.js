export const normalizeJa = (input = '') =>
    String(input)
    .normalize('NFKC')
    .toLowerCase()
    .replace(/\s+/g, '')
    .replace(/[\u30a1-\u30f6]/g, ch =>
            String.fromCharCode(ch.charCodeAt(0) - 0x60)
    );

export const normalizeFields = (...fields) =>
    normalizeJa(fields.filter(Boolean).join(' '));
