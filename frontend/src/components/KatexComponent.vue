<template>
    <div class="inline-katex" ref="root">
        <template v-for="(part, idx) in parts" :key="idx">
            <span v-if="part.type === 'text'">{{ part.content }}</span>

            <!-- data-tex и data-display позволяют при copy получить исходный LaTeX -->
            <span
                v-else
                v-html="part.html"
                :data-tex="part.content"
                :data-display="part.display ? '1' : '0'"
                :class="{ 'display-math': part.display }"
            ></span>
        </template>
    </div>
</template>

<script>
import katex from 'katex';
import 'katex/dist/katex.min.css';

export default {
    name: 'InlineKatex',
    props: {
        text: {type: String, default: ''}
    },
    data() {
        return {parts: []};
    },
    mounted() {
        this.processText();
        // перехватываем copy только внутри root
        this.$refs.root.addEventListener('copy', this.onCopy);
    },
    beforeUnmount() {
        this.$refs.root.removeEventListener('copy', this.onCopy);
    },
    watch: {
        text() {
            this.processText();
        }
    },
    methods: {
        parseToParts(s) {
            const parts = [];
            if (!s) return parts;
            let i = 0, L = s.length;
            while (i < L) {
                const ch = s[i];
                if (ch === '\\' && i + 1 < L) {
                    parts.push({type: 'text', content: s[i + 1]});
                    i += 2;
                    continue;
                }
                if (ch === '$') {
                    if (i + 1 < L && s[i + 1] === '$') {
                        let j = i + 2, found = -1;
                        while (j < L - 1) {
                            if (s[j] === '\\') {
                                j += 2;
                                continue;
                            }
                            if (s[j] === '$' && s[j + 1] === '$') {
                                found = j;
                                break;
                            }
                            j++;
                        }
                        if (found !== -1) {
                            parts.push({type: 'math', content: s.slice(i + 2, found), display: true});
                            i = found + 2;
                            continue;
                        } else {
                            parts.push({type: 'text', content: '$$'});
                            i += 2;
                            continue;
                        }
                    } else {
                        let j = i + 1, found = -1;
                        while (j < L) {
                            if (s[j] === '\\') {
                                j += 2;
                                continue;
                            }
                            if (s[j] === '$') {
                                found = j;
                                break;
                            }
                            j++;
                        }
                        if (found !== -1) {
                            parts.push({type: 'math', content: s.slice(i + 1, found), display: false});
                            i = found + 1;
                            continue;
                        } else {
                            parts.push({type: 'text', content: '$'});
                            i += 1;
                            continue;
                        }
                    }
                }
                let j = i, buf = '';
                while (j < L && s[j] !== '\\' && s[j] !== '$') {
                    buf += s[j];
                    j++;
                }
                parts.push({type: 'text', content: buf});
                i = j;
            }
            return parts;
        },

        processText() {
            const raw = this.text || '';
            const parsed = this.parseToParts(raw);

            this.parts = parsed.map(p => {
                if (p.type === 'math') {
                    let html;
                    try {
                        html = katex.renderToString(p.content, {throwOnError: false, displayMode: !!p.display});
                    } catch (e) {
                        html = `<code style="color:crimson">KaTeX error</code>`;
                    }
                    return {...p, html};
                } else return p;
            });
        },

        // обработчик события copy
        onCopy(e) {
            try {
                const sel = window.getSelection();
                if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
                    // если ничего не выделено — копируем весь исходный текст
                    e.clipboardData.setData('text/plain', this.text || '');
                    e.preventDefault();
                    return;
                }

                let result = '';
                for (let r = 0; r < sel.rangeCount; r++) {
                    const range = sel.getRangeAt(r);
                    const frag = range.cloneContents();
                    result += this.fragmentToLatex(frag);
                }

                // положим результат в буфер обмена как plain text (LaTeX)
                e.clipboardData.setData('text/plain', result);
                // опционально: можно также положить HTML-версию,
                // но тут мы хотим LaTeX главным образом
                e.preventDefault();
            } catch (err) {
                // в случае проблем — fallback: ничего не ломаем, позволим стандартному копированию
                console.error('copy handler error', err);
            }
        },

        // рекурсивно обходим DocumentFragment / Node и строим LaTeX-строку
        fragmentToLatex(node) {
            let out = '';

            const nodeType = node.nodeType;
            // TEXT_NODE
            if (nodeType === Node.TEXT_NODE) {
                out += node.nodeValue;
                return out;
            }

            // ELEMENT_NODE (включая фрагменты)
            if (nodeType === Node.ELEMENT_NODE || nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
                // если элемент содержит data-tex — это наша формула
                if (node.nodeType === Node.ELEMENT_NODE) {
                    const el = node;
                    const dataTex = el.getAttribute && el.getAttribute('data-tex');
                    const dataDisplay = el.getAttribute && el.getAttribute('data-display');
                    if (dataTex != null) {
                        // при вставке используем исходный вид: $$..$$ или $..$
                        const tex = dataTex;
                        if (dataDisplay === '1' || dataDisplay === 'true') {
                            return `$$${tex}$$`;
                        } else {
                            return `$${tex}$`;
                        }
                    }
                }

                // иначе обходим детей в порядке
                const children = node.childNodes;
                for (let i = 0; i < children.length; i++) {
                    out += this.fragmentToLatex(children[i]);
                }
                return out;
            }

            // прочие типы — игнорируем
            return out;
        },

        // удобная кнопка: скопировать весь исходный текст в буфер
        copyOriginalToClipboard() {
            if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(this.text || '').catch(err => console.error(err));
            } else {
                // fallback временный: создаём textarea и копируем
                const t = document.createElement('textarea');
                t.value = this.text || '';
                document.body.appendChild(t);
                t.select();
                try {
                    document.execCommand('copy');
                } catch (e) {
                    console.error(e);
                }
                document.body.removeChild(t);
            }
        }
    }
};
</script>

<style scoped>
.inline-katex {
    line-height: 1.4;
}

.display-math {
    display: block;
    margin: 0.5em 0;
}
</style>
