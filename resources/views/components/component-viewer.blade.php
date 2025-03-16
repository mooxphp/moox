@props(['code'])

<div x-data="{
    activeTab: 'view',
    theme: 'dark',
    componentCode: @js($code)
}" class="mb-10 border border-pink-500/20 rounded-lg overflow-hidden">
    <div class="flex justify-between items-center bg-slate-900 p-3 border-b border-pink-500/20">
        <div class="flex space-x-2">
            <button
                @click="activeTab = 'view'"
                :class="{ 'bg-pink-500/20 text-pink-200': activeTab === 'view', 'text-gray-400 hover:text-gray-200': activeTab !== 'view' }"
                class="px-4 py-2 rounded-md transition-colors">
                View
            </button>
            <button
                @click="activeTab = 'code'"
                :class="{ 'bg-pink-500/20 text-pink-200': activeTab === 'code', 'text-gray-400 hover:text-gray-200': activeTab !== 'code' }"
                class="px-4 py-2 rounded-md transition-colors">
                Code
            </button>
        </div>
        <div>
            <button x-show="activeTab === 'view'" @click="theme = theme === 'dark' ? 'light' : 'dark'" class="text-gray-400 hover:text-gray-200">
                <span x-show="theme === 'dark'" class="material-symbols-rounded">light_mode</span>
                <span x-show="theme === 'light'" class="material-symbols-rounded">dark_mode</span>
            </button>
            <button x-show="activeTab === 'code'" @click="navigator.clipboard.writeText(componentCode)" class="text-gray-400 hover:text-gray-200">
                <span class="material-symbols-rounded">content_copy</span>
            </button>
        </div>
    </div>

    <div class="p-6" :class="{ 'bg-slate-950': theme === 'dark', 'bg-white': theme === 'light' }">
        <div x-show="activeTab === 'view'" :class="{ 'text-gray-200': theme === 'dark', 'text-gray-800': theme === 'light' }">
            <div class="flex justify-center items-center p-4">
                <div x-html="componentCode.replace(/</g, '&lt;').replace(/>/g, '&gt;')"></div>
            </div>
        </div>

        <div x-show="activeTab === 'code'" class="code-content" :class="{ 'text-gray-200': theme === 'dark', 'text-gray-800': theme === 'light' }">
            <pre class="line-numbers"><code class="language-markup-templating language-php line-numbers" x-init="
                setTimeout(() => {
                    $el.textContent = componentCode;
                    Prism.highlightElement($el);
                }, 50);
            "></code></pre>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const style = document.createElement('style');
        style.textContent = `
            .code-content pre {
                background: #0f172a !important;
                border-radius: 0.375rem;
                margin: 0;
            }
            .code-content .line-numbers .line-numbers-rows {
                border-right-color: rgba(236, 72, 153, 0.2) !important;
            }
            .code-content .token.comment,
            .code-content .token.prolog,
            .code-content .token.doctype,
            .code-content .token.cdata {
                color: #6b7280;
            }
            .code-content .token.punctuation {
                color: #e2e8f0;
            }
            .code-content .token.namespace {
                opacity: .7;
            }
            .code-content .token.property,
            .code-content .token.tag,
            .code-content .token.boolean,
            .code-content .token.number,
            .code-content .token.constant,
            .code-content .token.symbol,
            .code-content .token.deleted {
                color: #ec4899;
            }
            .code-content .token.selector,
            .code-content .token.attr-name,
            .code-content .token.string,
            .code-content .token.char,
            .code-content .token.builtin,
            .code-content .token.inserted {
                color: #a5b4fc;
            }
            .code-content .token.operator,
            .code-content .token.entity,
            .code-content .token.url,
            .code-content .language-css .token.string,
            .code-content .style .token.string {
                color: #d1d5db;
            }
            .code-content .token.atrule,
            .code-content .token.attr-value,
            .code-content .token.keyword {
                color: #8b5cf6;
            }
            .code-content .token.function,
            .code-content .token.class-name {
                color: #f472b6;
            }
            .code-content .token.regex,
            .code-content .token.important,
            .code-content .token.variable {
                color: #f59e0b;
            }
        `;
        document.head.appendChild(style);
    });

    document.addEventListener('alpine:initialized', () => {
        Alpine.effect(() => {
            const activeTab = Alpine.$data(document.querySelector('[x-data]')).activeTab;
            if (activeTab === 'code') {
                setTimeout(() => {
                    Prism.highlightAll();
                }, 50);
            }
        });
    });
</script>