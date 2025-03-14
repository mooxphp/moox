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
            <button x-show="activeTab === 'code'" @click="navigator.clipboard.writeText(document.querySelector('.code-content').textContent)" class="text-gray-400 hover:text-gray-200">
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
            <textarea id="blade-code" class="h-32">{{ $code }}</textarea>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let editor = CodeMirror.fromTextArea(document.getElementById("blade-code"), {
            mode: "application/x-httpd-php",
            theme: "ayu-dark",
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            tabSize: 4,
            indentUnit: 4,
            indentWithTabs: true,
            viewportMargin: 10,
            height: "100px"
        });

        editor.setSize(null, "200px");
    });
</script>