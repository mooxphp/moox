<div class="relative">
    <div x-data="markdown()" x-ref="markdownX" data-key="{{ $key }}" id="markdown-{{ $key }}" class="relative w-full" x-init="init()">
        {{-- MarkdownX Toolbar --}}
        <div id="markdownx-insert-{{ $key }}" :data-insert="editStart"></div>
        <div class="@if(isset($style['toolbar'])){{ $style['toolbar'] }}@else{{ 'relative flex items-center justify-between w-full h-12 overflow-x-hidden bg-gray-50 sm:h-10' }}@endif">
            <div class="flex items-center h-12 sm:h-10">
                <div class="flex items-center h-full px-4 font-medium text-gray-600 cursor-pointer hover:bg-gray-100 dark-hover:bg-dark-950" @click="section = 'write'" x-bind:class="{ 'text-blue-500 border-b border-blue-500' : section == 'write' }">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-4 h-4 mr-2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Write</span>
                </div>
                <div wire:click="updateContentPreview()" class="flex items-center h-full px-4 font-medium text-gray-600 cursor-pointer hover:bg-gray-100 dark-hover:bg-dark-950" @click="section = 'preview'" x-bind:class="{ 'text-blue-500 border-b border-blue-500' : section == 'preview' }">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-4 h-4 mr-2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <span>Preview</span>
                </div>
                <div class="flex items-center h-full px-4 font-medium text-gray-600 cursor-pointer hover:bg-gray-100 dark-hover:bg-dark-950" @click="section = 'help'" x-bind:class="{ 'text-blue-500 border-b border-blue-500' : section == 'help' }">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-4 h-4 mr-2"><path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Help</span>
                </div>
            </div>
            <div class="relative flex items-center h-full px-4 font-medium text-gray-600 cursor-pointer hover:bg-gray-100 dark-hover:bg-dark-950">
                <input type="file" x-on:change="upload(event, '{{ $key }}')" x-ref="image" id="image-{{ $key }}" class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer" tabindex="-1">
                <svg  fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6 cursor-pointer"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" class="cursor-pointer"></path></svg>
            </div>
        </div>

        {{-- MarkdownX Editor --}}
        <div class="relative z-40" x-show="section == 'write'">
            <div x-ref="error" id="error-{{ $key }}" class="absolute top-0 z-40 hidden w-full py-2 text-sm text-center text-red-400 bg-red-50"></div>
            <div x-ref="editorModal" x-show="popup" x-ref="popup" x-on:keydown.escape="cancelModal(); popup=false" x-on:click.away="popup=false" wire:ignore :class="{ 'translate-y-2 scale-100 transition-transform duration-100 ease-in-out': popup, 'translate-y-0 scale-95': !popup,  'max-w-sm' : popupType != 'code', 'max-w-4xl pr-10' : popupType == 'code' }" class="absolute z-40 w-full max-w-sm transform rounded-lg shadow-sm" x-cloak>
                <div class="absolute left-0 w-4 h-4 -mt-2 ml-3.5 transform rotate-45 bg-white border-t border-l border-gray-200 rounded-tl-sm"></div>
                <div class="overflow-hidden border border-gray-200 rounded-lg">
                    <div class="px-5 py-4 bg-white">
                        <div x-ref="editorModalContent"></div>
                    </div>
                    <div class="px-5 py-3 bg-gray-100 sm:flex sm:flex-row-reverse">
                        <span class="flex w-full rounded-md sm:ml-3 sm:w-auto">
                            <button id="modalClose" @click="cancelModal(); popup = false;" type="button" class="inline-flex justify-center w-full px-4 py-2 mr-2 text-base font-medium leading-6 text-gray-700 transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline sm:text-sm sm:leading-5">Cancel</button>
                            <button type="button" x-ref="modalExecute" id="modal-execute-{{ $key }}" @click="executeAssociatedFunction()" data-suggestion="" @click="popup = false" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium leading-6 text-white transition duration-150 ease-in-out bg-blue-500 border-blue-500 rounded-md shadow-sm text-whit hover:border-blue-600 hover:bg-blue-600 focus:outline-none focus:border-blue-300 focus:shadow-outline sm:text-sm sm:leading-5">Insert</button>
                        </span>
                    </div>
                </div>
            </div>
            <div id="dropdown-{{ $key }}" x-ref="dropdown" @click="clickItem($event)" wire:ignore class="relative z-40"></div>
            <div wire:ignore x-show="debug" @click="$refs.editor.focus()" :class="{ 'w-full h-full bg-red-100 bg-opacity-50' : debuggerOpen, 'w-0 h-auto' : !debuggerOpen }" class="absolute z-40 cursor-text" x-cloak>
                    <div x-show="debuggerOpen" x-ref="debugger" class="w-full opacity-75" x-cloak></div>
                    <div x-ref="debugButton" class="relative opacity-0">
                        <div @click="debuggerOpen=!debuggerOpen" :class="{ 'text-gray-400 bg-gray-50 hover:text-gray-500' : !debuggerOpen, 'text-red-400 hover:text-red-500 bg-red-50' : debuggerOpen }" class="absolute top-0 flex items-center justify-center rounded-sm cursor-pointer -ml-9 group w-9 h-9">
                            <svg x-show="debuggerOpen" class="w-5 h-5 transform rotate-90 stroke-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M0 0h24v24H0z" stroke="none"/><path d="M9 9V8a3 3 0 016 0v1M8 9h8a6 6 0 011 3v3a5 5 0 01-10 0v-3a6 6 0 011-3M3 13h4M17 13h4M12 20v-6M4 19l3.35-2M20 19l-3.35-2M4 7l3.75 2.4M20 7l-3.75 2.4"/></svg>
                            <svg
                                x-show="!debuggerOpen"
                                x-transition:enter-start="rotate-90"
                                x-transition:enter-end="rotate-0"
                                class="w-5 h-5 transition-all duration-500 ease-out transform stroke-current group-hover:rotate-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M0 0h24v24H0z" stroke="none"/><path d="M9 9V8a3 3 0 016 0v1M8 9h8a6 6 0 011 3v3a5 5 0 01-10 0v-3a6 6 0 011-3M3 13h4M17 13h4M12 20v-6M4 19l3.35-2M20 19l-3.35-2M4 7l3.75 2.4M20 7l-3.75 2.4"/></svg>
                        </div>
                    </div>
                    <div x-show="debuggerOpen" class="fixed bottom-0 right-0 px-3 py-2 bg-red-50">
                        <div class="flex text-sm text-red-500">
                            <span>Cursor Start:</span>
                            <span class="mr-2" x-text="currentCaretPos.start"></span>
                            <span>Cursor End:</span>
                            <span class="mr-2" x-text="currentCaretPos.end"></span>
                        </div>
                    </div>
                </div>
            <div class="relative z-30 overflow-hidden @if(isset($style['height'])){{ $style['height'] }}@endif">

                <div wire:ignore x-ref="placeholder" @click="$refs.editor.focus()" id="placeholder-{{ $key }}" x-show="placeholder" class="absolute z-20 text-gray-400 transition-opacity duration-200 ease-out" x-cloak>Type '/' for commands </div>
                <textarea x-ref="editor" id="editor-{{ $key }}" data-key="{{ $key }}" class="editors @if(isset($style['textarea'])){{ $style['textarea'] }}@else{{ 'w-full h-full mx-auto min-h-screen px-5 md:px-1 pt-5 font-mono leading-loose tracking-tighter border-0 outline-none focus:outline-none sm:x-0 text-lg text-gray-600' }}@endif" placeholder="" data-loaded="false" name="{{ $name }}" x-on:dragenter="$event.preventDefault(); dropFiles=true" wire:model.lazy="content" x-on:blur="@this.call('update', { content: $event.target.value });" x-on:focus="editorEvent($event)" x-on:keypress="editorEvent($event)" x-on:keydown="getCursorXY(); editorEvent($event)" x-on:keyup="getCursorXY(); editorEvent($event)" x-on:click="editorEvent($event)" x-cloak>
                    </textarea>
                <div x-ref="drop" x-show="dropFiles" x-on:dragleave="$event.preventDefault(); dropFiles=false" x-on:dragover="$event.preventDefault();" x-on:drop="$event.preventDefault(); droppingFile($event)" class="absolute inset-0 flex items-center justify-center w-full h-full bg-blue-100 bg-opacity-20" x-cloak>
                    <div class="flex flex-col items-center justify-center w-40 h-32 text-xs text-gray-400 bg-white border-0 border-gray-200 border-dashed rounded-lg">
                        <svg class="w-12 h-auto mb-3 fill-current" viewBox="0 0 98 97" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero"><path d="M63.48.16H2.76a2 2 0 00-2 2v60.73a2 2 0 002 2h23.59a2 2 0 100-4H4.78V4.22h56.66v21.57a2 2 0 104 0V2.17a2 2 0 00-2-2M95.35 89.08A1.91 1.91 0 0093.44 91v1.9h-1.9a1.91 1.91 0 000 3.81h3.81a1.91 1.91 0 001.9-1.9V91a1.91 1.91 0 00-1.9-1.9M77.55 92.88h-6.32a1.91 1.91 0 000 3.81h6.32a1.91 1.91 0 100-3.81M57.24 92.88h-6.32a1.91 1.91 0 100 3.81h6.32a1.91 1.91 0 000-3.81M38.19 92.88h-1.91V91a1.9 1.9 0 00-3.8 0v3.81a1.91 1.91 0 001.9 1.9h3.81a1.91 1.91 0 000-3.81"/><path d="M34.38 58.58a1.91 1.91 0 001.9-1.9v-6.32a1.9 1.9 0 00-3.8 0v6.32a1.89 1.89 0 001.9 1.9M32.48 77a1.9 1.9 0 003.8 0v-6.33a1.9 1.9 0 00-3.8 0V77zM38.19 31.92h-3.81a1.9 1.9 0 00-1.9 1.9v3.8a1.9 1.9 0 003.8 0v-1.9h1.91a1.91 1.91 0 001.9-1.9 1.89 1.89 0 00-1.9-1.9M57.24 31.92h-6.32a1.9 1.9 0 000 3.8h6.32a1.91 1.91 0 001.9-1.9 1.89 1.89 0 00-1.9-1.9M77.55 31.92h-6.32a1.9 1.9 0 000 3.8h6.32a1.9 1.9 0 000-3.8M95.35 31.92h-3.81a1.9 1.9 0 000 3.8h1.9v1.9a1.91 1.91 0 103.81 0v-3.8a1.91 1.91 0 00-1.9-1.9M95.35 68.74a1.9 1.9 0 00-1.91 1.9V77a1.91 1.91 0 103.81 0v-6.33a1.93 1.93 0 00-1.9-1.93M95.35 48.42a1.91 1.91 0 00-1.91 1.91v6.35a1.91 1.91 0 003.81 0v-6.32a1.93 1.93 0 00-1.9-1.94M81.41 62.81a1.29 1.29 0 01-.89 1.19l-10.33 3.65a3.26 3.26 0 00-2 2L64.57 80a1.29 1.29 0 01-1.23.89 1.34 1.34 0 01-1.26-.89L51.45 52.65a1.28 1.28 0 01.31-1.43 1.3 1.3 0 01.92-.39c.17.001.338.028.5.08l27.39 10.64a1.2 1.2 0 01.84 1.26"/></g></svg>
                        <span>Drop Files Here</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- MarkdownX Preview Section --}}
        <div x-show="section == 'preview'" wire:target="updateContentPreview" class="@if(isset($style['preview'])){{ $style['preview'] }}@else{{ 'h-full bg-white min-h-screen relative z-30 px-5 pt-5 prose md:prose-xl lg:prose-2xl max-w-none' }}@endif" x-cloak>
            {!! $contentPreview !!}
        </div>
        {{-- End: MarkdownX Preview Section --}}

        {{-- MarkdownX Help Section --}}
        <div x-show="section == 'help'" class="@if(isset($style['help'])){{ $style['help'] }}@else{{ 'h-full bg-white min-h-screen px-5 pt-10 relative z-30 prose bg-white dark:bg-dark-900 md:prose-xl lg:prose-lg max-w-none' }}@endif" x-cloak>

            <h2>Markdown Basics</h2>
            <p>Below you will find some common used markdown syntax. For a deeper dive in Markdown check out this <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Here-Cheatsheet" target="_blank">Cheat Sheet</a></p>
            <hr>

            <h3>Bold & Italic</h3>
            <p><span class="mr-2 italic">Italics</span> <span class="p-1">*asterisks*</span><br><span class="mr-2 font-bold">Bold</span> <span class="p-1">**double asterisks**</span></p>
            <hr>

            <h3>Code</h3>
            <p><span class="mr-2">Inline Code</span><br><span class="p-1 font-mono text-blue-500 bg-blue-100 rounded-md">`backtick`</span><span class="block mt-2 mr-2">Code Block</span><span class="block p-1 font-mono text-blue-600 bg-blue-100">```<br>Three back ticks and then enter your code blocks here.<br>```</span></p>
            <hr>

            <h3>Headers</h3>
            <p># This is a Heading 1<br>## This is a Heading 2<br>### This is a Heading 3<br></p>
            <hr>

            <h3>Quotes</h3>
            <blockquote> > type a greater than sign and start typing your quote.</blockquote>
            <hr>

            <h3>Links</h3>
            <p>You can add <a href="#_">links</a> by adding text inside of <span class="p-1 font-mono text-blue-600 bg-blue-100">[]</span> and the link inside of <span class="p-1 font-mono text-blue-600 bg-blue-100">()</span>, like so:</p>
            <div class="p-1 font-mono text-blue-600 bg-blue-100">[link_text](https://google.com)</div>
            <hr>

            <h3>Lists</h3>
            <p>To add a numbered list you can simply start with a number and a <span class="p-1 font-mono text-blue-600 bg-blue-100">.</span>, like so:<br><span class="block p-1 pl-5 font-mono text-blue-600 bg-blue-100"> 1. The first item in my list</span></p>
            <p>For an unordered list, you can add a dash <span class="p-1 font-mono text-blue-600 bg-blue-100">-</span>, like so:<br><span class="block p-1 pl-5 font-mono text-blue-600 bg-blue-100"> - The start of my list</span></p>
            <hr>

            <h3>Images</h3>
            <p>You can add images by selecting the image icon, which will upload and add an image to the editor, or you can manually add the image by adding an exclamation <span class="p-1 font-mono text-blue-600 bg-blue-100">!</span>, followed by the alt text inside of <span class="p-1 font-mono text-blue-600 bg-blue-100">[]</span>, and the image URL inside of <span class="p-1 font-mono text-blue-600 bg-blue-100">()</span>, like so:</p>
            <div class="p-1 font-mono text-blue-600 bg-blue-100">![alt text for image](url_to_image.png)</div>
            <hr>

            <h3>Dividers</h3>
            <p>To add a divider you can add three dashes or three asterisks:<br><span class="block p-1 pl-5 font-mono text-blue-600 bg-blue-100">--- or ***</span>
            </p>
            <hr>

            @if(in_array('giphy', config('markdownx.dropdown_items')))
            <h3>Embedding GIFs via Giphy</h3>
            <p>You can easily embed animated GIFS with the following syntax:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% giphy https://giphy.com/embed/giphy_id %}</span></p>
            <hr>
            @endif

            @if(in_array('codepen', config('markdownx.dropdown_items')))
            <h3>Embedding Codepens</h3>
            <p>You can also embed a codepen by writing the following:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% codepen https://codepen.io/your/pen/url %}</span></p>
            <p>You may also choose the default tabs you wish to show your pen by writing the <span class="p-1 font-mono text-blue-600 bg-blue-100">default-tab</span> like so: (default is result)</p>
            <p><span class="p-1 font-mono text-blue-600 bg-blue-100">{% codepen https://codepen.io/your/pen/url default-tab=result,html %}</span></p>
            <hr>
            @endif

            @if(in_array('codesandbox', config('markdownx.dropdown_items')))
            <h3>Embedding CodeSandbox</h3>
            <p>You can also embed CodeSandbox by writing the following:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% codesandbox YOUR_CODESANDBOX_EMBED_URL %}</span></p>
            <hr>
            @endif

            @if(in_array('gists', config('markdownx.dropdown_items')))
            <h3>Embedding Gists</h3>
            <p>You can also embed a Gists by writing the following:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% gist GIST_ID_HERE %}</span></p>
            <hr>
            @endif

            @if(in_array('youtube', config('markdownx.dropdown_items')))
            <h3>Embedding YouTube Videos</h3>
            <p>You can also embed a YouTube video by writing the following:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% youtube VIDEO_ID_HERE %}</span></p>
            <hr>
            @endif

            @if(in_array('buy_me_a_coffee', config('markdownx.dropdown_items')))
            <h3>Embedding buymeacoffee.com</h3>
            <p>You can also embed your "Buy me a coffee" button by writing the following:<br><span class="p-1 font-mono text-blue-600 bg-blue-100">{% buymeacoffee BUY_ME_A_COFFEE_USERNAME_HERE %}</span></p>
            <hr>
            @endif
        </div>
        {{-- End: MarkdownX Help Section --}}

    </div>

    <script>
        const suggestionClasses = 'overflow-scroll';
        const suggestionActiveClasses = 'bg-gray-50 text-white suggestion-active';
        const suggestionItemClasses = 'cursor-pointer hover:bg-gray-50';

        window.markdown = function() {
            return {
                section: @entangle('section'),
                autofocus: true,
                dropdownEl: null,
                popup: false,
                popupType: '',
                placeholder: false,
                editStart: 0,
                isCurrentLineEmpty: true,
                suggestionDropdown: false,
                currentCaretPos: 0,
                codeEditor: null,
                debug: false,
                debuggerOpen: false,
                dropFiles: false,
                dynamicEditorEvents: [], // use this to prevent duplicate dynamic events from being created.
                init(){
                    let that = this;

                    this.$watch('placeholder', function(value){
                        if(!value){
                            that.repositionPlaceholder();
                        }
                    });

                    setTimeout(function(){
                        //that.placeholder = true;
                        if(that.autofocus){
                            that.$refs.editor.focus();
                        }
                    }, 1);

                    this.loop();

                    this.$refs.editor.addEventListener('scroll', function(e) {

                        if(that.getCurrentLine() == ""){
                            that.getCursorXY();
                            that.repositionPlaceholder();
                        } else {
                            that.placeholder = false;
                        }

                        that.$refs.debugger.firstChild.scrollTop = that.$refs.editor.scrollTop;
                    });

                    this.$refs.debugger.firstChild.addEventListener('scroll', function(e) {
                        that.$refs.editor.scrollTop = that.$refs.debugger.firstChild.scrollTop;
                    });
                },
                loop() {
                    // Do stuff
                    let newCurrentCaretPos = this.$refs.editor.getCaretPosition();

                    if(this.currentCaretPos.start != newCurrentCaretPos.start || this.currentCaretPos.end != newCurrentCaretPos.end){
                        this.currentCaretPos = newCurrentCaretPos;
                        this.getCursorXY();
                    }
                    requestAnimationFrame(() => this.loop());
                },
                upload(evt, key) {

                    let file = evt.target.files[0];

                    // if filesize is larger than 5MB, notify the user
                    if (file.size > (parseInt('{{ config("markdownx.image.max_file_size") }}') * 1000) ) {
                        showErrorMessage(this.$refs.error, 'File size too large, please upload an image smaller than ' + Math.round(parseInt('{{ config("markdownx.image.max_file_size") }}')/1000) + 'MB.')
                        return null;
                    } else {
                        // we have a file
                        if (evt.target.value.length) {

                            this.placeholder = false;
                            this.$refs.editor.insertAtCaret("![" + file.name + "](Uploading...)");
                            this.$refs.editor.focus();

                            let reader = new FileReader();

                            reader.onloadend = () => {
                                window.livewire.emit('markdown-x-image-upload', { image: reader.result, name: file.name, key: key, text: "![" + file.name + "](Uploading...)" });
                            }
                            reader.readAsDataURL(file);
                        }
                    }
                },
                cancelModal (){
                    this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                },
                getCursorXY () {
                    let editor = this.$refs.editor;
                    const { offsetLeft: inputX, offsetTop: inputY } = editor;
                    // create a dummy element that will be a clone of our input
                    const div = document.createElement("div");
                    // get the class name of the input and clone it to the dummy element
                    div.className = editor.className;
                    div.classList.remove('min-h-screen');
                    div.classList.remove('h-full');
                    div.classList.remove('w-full');
                    div.classList.add('whitespace-pre-wrap');
                    div.classList.add('break-words');

                    // we need a character that will replace whitespace when filling our dummy element if it's a single line <input/>
                    const swap = ".";
                    // set the div content to that of the textarea up until selection
                    const textContent = editor.value.substr(0, editor.selectionStart);
                    // set the text content of the dummy element div
                    div.textContent = textContent;
                    div.style.height = "auto";
                    // create a marker element to obtain caret position
                    const span = document.createElement("span");
                    span.className='bg-red-400';
                    // give the span the textContent of remaining content so that the recreated dummy element is as close as possible
                    let spanCharacters = ".";
                    if(editor.selectionStart != editor.selectionEnd){
                        spanCharacters = editor.value.substr(editor.selectionStart, editor.selectionEnd);
                    }
                    span.textContent = spanCharacters;
                    // append the span marker to the div
                    div.appendChild(span);
                    // append the dummy element to the body
                    this.$refs.dropdown.appendChild(div);
                    // get the marker position, this is the caret position top and left relative to the input
                    const { offsetLeft: spanX, offsetTop: spanY, lineHeight: lineHeight } = span;
                    // lastly, remove that dummy element
                    // NOTE:: can comment this out for debugging purposes if you want to see where that span is rendered
                    this.$refs.dropdown.removeChild(div);

                    // if the debugger is on
                    if(this.$refs.debugger){
                        div.classList.add('absolute');
                        div.classList.add('inset-0');
                        div.classList.add('text-red-400');
                        this.$refs.debugger.innerHTML = '';
                        this.$refs.debugger.appendChild(div);
                        if(this.$refs.debugButton.classList.contains('opacity-0')){
                            this.$refs.debugButton.classList.add('-mt-2');
                            this.$refs.debugButton.classList.remove('opacity-0');
                            this.$refs.debugButton.classList.add('opacity-100');
                        }
                        this.$refs.debugButton.style.top = (inputY + spanY) + `px`;
                    }

                    // return an object with the x and y of the caret. account for input positioning so that you don't need to wrap the input
                    return {
                        x: inputX + spanX,
                        y: inputY + spanY
                    };
                },
                suggestionDropdownItems () {
                    let dropdownItems = {
                        "text" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="w-6 h-6 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="1" fill-rule="evenodd"><g transform="translate(0 38)" fill-rule="nonzero"><path d="M200 6.551V0h-28.17v6.551h10.81v110.246h-10.81v6.551H200v-6.551h-10.81V6.55z"/><g transform="translate(0 13.985)"><path d="M1.215 82.698l32.97-74.455C36.486 3.11 40.673 0 46.35 0h1.216c5.674 0 9.729 3.108 12.025 8.243l32.972 74.455c.675 1.487 1.082 2.838 1.082 4.19 0 5.54-4.325 9.999-9.865 9.999-4.865 0-8.108-2.838-10-7.163l-6.35-14.863H25.81l-6.62 15.54c-1.758 4.053-5.27 6.486-9.594 6.486-5.408 0-9.595-4.324-9.595-9.73 0-1.486.54-2.972 1.215-4.459zm58.511-26.214L46.618 25.269 33.511 56.484h26.215zM103.502 75.266v-.27c0-15.81 12.027-23.107 29.188-23.107 7.297 0 12.568 1.216 17.703 2.973v-1.216c0-8.514-5.271-13.242-15.54-13.242-5.676 0-10.27.81-14.189 2.027-1.215.405-2.027.54-2.974.54-4.728 0-8.512-3.649-8.512-8.378 0-3.648 2.297-6.756 5.54-7.973 6.485-2.432 13.513-3.783 23.107-3.783 11.215 0 19.323 2.972 24.458 8.107 5.405 5.405 7.837 13.378 7.837 23.107v32.97c0 5.542-4.459 9.865-10 9.865-5.945 0-9.863-4.189-9.863-8.513v-.135c-5 5.54-11.893 9.188-21.89 9.188-13.65.001-24.865-7.835-24.865-22.16zm47.16-4.73v-3.648c-3.514-1.621-8.108-2.702-13.107-2.702-8.785 0-14.189 3.513-14.189 9.999v.27c0 5.54 4.595 8.783 11.215 8.783 9.594 0 16.08-5.269 16.08-12.702z" /></g></g></g></svg></span>`,
                            "title" : `Text`,
                            "description" : `Start writing with plain text.`,
                            "display" : `inline`
                        },
                        "heading" : {
                            "icon" : `<span class="p-1.5 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="1" fill-rule="evenodd"><g transform="translate(33 51)" fill-rule="nonzero"><path d="M38.095 97c1.369 0 2.36-.325 2.974-.974.614-.65.92-1.787.92-3.41 0-1.764-.4-3.48-1.203-5.15h-8.073c-.188-4.919-.306-16.403-.354-34.451h35.192c-.047 18.048-.189 29.532-.425 34.45h-5.169c-1.369 0-2.395.29-3.08.87-.684.58-1.027 1.497-1.027 2.75 0 1.902.402 3.873 1.204 5.915h36.891c1.322 0 2.29-.325 2.903-.974.614-.65.92-1.787.92-3.41 0-1.764-.4-3.48-1.203-5.15h-8.71c-.257-5.416-.398-18.796-.42-40.14l-.004-4.231v-2.19c-.047-13.641.07-23.431.354-29.37h6.16c1.322 0 2.29-.313 2.903-.94.614-.626.92-1.774.92-3.445 0-1.717-.4-3.433-1.203-5.15H61.957c-1.369 0-2.395.29-3.08.87-.684.58-1.027 1.52-1.027 2.819 0 1.856.402 3.804 1.204 5.846h8.001c.284 5.939.449 15.891.496 29.857H32.36c-.047-13.966.048-23.918.284-29.857h5.452c1.369 0 2.36-.313 2.974-.94.614-.626.92-1.774.92-3.445 0-1.717-.4-3.433-1.203-5.15H4.178c-1.37 0-2.408.29-3.116.87C.354 3.45 0 4.39 0 5.689c0 1.856.425 3.804 1.275 5.846h8.709c.236 5.939.378 15.706.425 29.3l-.001 4.338c-.013 22.605-.131 36.703-.353 42.292H4.178c-1.37 0-2.408.29-3.116.87C.354 88.915 0 89.832 0 91.085 0 92.986.425 94.957 1.275 97h36.82zM141 97V85.825h-9.847V27.158h-24.362V39.13h11.065v46.694h-10.66V97H141z" /></g></g></svg></span>`,
                            "title" : `Heading`,
                            "description" : `Large heading text.`,
                            "display" : `block`
                        },
                        "heading_2" : {
                            "icon" : `<span class="p-1.5 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="1" fill-rule="evenodd"><g transform="translate(33 51)" fill-rule="nonzero"><path d="M37.96 97c1.364 0 2.351-.325 2.963-.974.611-.65.917-1.787.917-3.41 0-1.764-.4-3.48-1.2-5.15h-8.043c-.188-4.919-.306-16.403-.353-34.451h35.067c-.047 18.048-.188 29.532-.423 34.45h-5.151c-1.364 0-2.387.29-3.07.87-.681.58-1.022 1.497-1.022 2.75 0 1.902.4 3.873 1.2 5.915h36.76c1.316 0 2.28-.325 2.892-.974.611-.65.917-1.787.917-3.41 0-1.764-.4-3.48-1.2-5.15h-8.678c-.257-5.416-.397-18.796-.42-40.14l-.003-4.231v-2.19c-.047-13.641.07-23.431.353-29.37h6.138c1.317 0 2.282-.313 2.893-.94.611-.626.917-1.774.917-3.445 0-1.717-.4-3.433-1.2-5.15H61.738c-1.364 0-2.387.29-3.07.87-.681.58-1.022 1.52-1.022 2.819 0 1.856.4 3.804 1.2 5.846h7.972c.282 5.939.447 15.891.494 29.857H32.244c-.047-13.966.047-23.918.283-29.857h5.432c1.365 0 2.352-.313 2.964-.94.611-.626.917-1.774.917-3.445 0-1.717-.4-3.433-1.2-5.15H4.164c-1.364 0-2.4.29-3.105.87C.353 3.45 0 4.39 0 5.689c0 1.856.423 3.804 1.27 5.846h8.678c.236 5.939.377 15.706.424 29.3l-.001 4.338c-.013 22.605-.13 36.703-.352 42.292H4.163c-1.364 0-2.4.29-3.105.87C.353 88.915 0 89.832 0 91.085 0 92.986.423 94.957 1.27 97h36.69zM167 97V84.728h-44V78.74c0-7.583 9.508-9.08 15.375-9.08 14.869 0 28.423-3.392 28.423-21.151 0-16.563-15.375-22.25-29.536-22.25-13.048 0-27.21 6.386-27.31 21.152h13.25c.101-6.286 7.283-9.778 14.262-9.778 9.104 0 16.083 3.592 16.083 11.175 0 8.082-9.811 8.98-15.172 8.98-13.96 0-28.727 4.789-28.727 20.853V97H167z" /></g></g></svg></span>`,
                            "title" : `Heading 2`,
                            "description" : `Medium heading text.`,
                            "display" : `block`
                        },
                        "heading_3" : {
                            "icon" : `<span class="p-1.5 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="1" fill-rule="evenodd"><g transform="translate(33 51)" fill-rule="nonzero"><path d="M37.756 95.992c1.357 0 2.34-.328 2.948-.985.608-.656.912-1.805.912-3.446 0-1.781-.397-3.516-1.193-5.204h-8c-.187-4.969-.304-16.572-.351-34.81H66.95c-.047 18.238-.187 29.841-.421 34.81h-5.123c-1.357 0-2.375.294-3.053.88-.678.586-1.018 1.511-1.018 2.777 0 1.923.398 3.915 1.193 5.978h36.564c1.31 0 2.269-.328 2.877-.985.608-.656.912-1.805.912-3.446 0-1.781-.397-3.516-1.193-5.204h-8.632c-.256-5.471-.395-18.99-.417-40.558l-.003-4.275V39.31c-.047-13.783.07-23.676.35-29.677h6.106c1.31 0 2.269-.316 2.877-.949.608-.633.912-1.793.912-3.481 0-1.735-.397-3.47-1.193-5.204H61.407c-1.357 0-2.375.293-3.053.879-.678.586-1.018 1.535-1.018 2.848 0 1.875.398 3.845 1.193 5.907h7.93c.281 6.001.445 16.058.492 30.17H32.07c-.046-14.112.048-24.169.282-30.17h5.403c1.357 0 2.34-.316 2.948-.949.608-.633.912-1.793.912-3.481 0-1.735-.397-3.47-1.193-5.204H4.141C2.784 0 1.754.293 1.053.879.35 1.465 0 2.414 0 3.727c0 1.875.421 3.845 1.263 5.907h8.632c.234 6.001.375 15.87.421 29.607v4.383c-.014 22.841-.13 37.086-.35 42.733H4.14c-1.357 0-2.387.294-3.088.88C.35 87.823 0 88.748 0 90.014c0 1.923.421 3.915 1.263 5.978h36.493zM135.22 97C148.802 97 165 91.858 165 74.619c0-5.948-4.729-12.602-11.47-14.82 6.138-1.916 9.76-8.368 9.76-13.913-.101-15.021-14.387-21.474-27.668-21.474-12.978 0-28.07 5.545-28.07 20.769h13.28c0-6.453 8.452-8.67 14.69-8.67 11.268 0 14.487 5.343 14.487 9.375-.1 6.553-7.646 8.469-14.79 8.469h-10.16v10.888h10.966c12.676 0 14.99 5.242 14.99 9.477 0 7.56-8.954 10.384-15.695 10.384-5.734 0-15.493-2.52-15.493-9.78h-13.381C106.546 91.355 121.839 97 135.22 97z" /></g></g></svg></span>`,
                            "title" : `Heading 3`,
                            "description" : `Small heading text.`,
                            "display" : `block`
                        },
                        "image" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="w-6 h-6 text-gray-800 fill-current" viewBox="0 0 197 200" xmlns="http://www.w3.org/2000/svg"><g transform="translate(.6)" fill-rule="nonzero" fill="none"><path d="M27.77 0h140.405c15.288 0 27.769 12.48 27.769 27.77v144.46c0 15.29-12.48 27.77-27.77 27.77H27.77C12.48 200 0 187.52 0 172.23V27.458C0 12.48 12.48 0 27.77 0z" fill="#4A566E"/><path d="M90.484 130.733L49.61 89.548 0 139.158v33.073C0 187.52 12.48 200 27.77 200h140.405c15.288 0 27.769-12.48 27.769-27.77v-53.354l-46.49-46.801-58.97 58.658z" fill="#00B594"/><circle fill="#FFCC03" cx="89.548" cy="66.147" r="17.473"/></g></svg></span>`,
                            "title" : `Image`,
                            "description" : `Upload or add an image.`,
                            "display" : `block`
                        },
                        "code" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="w-6 h-6 text-gray-700 fill-current" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#FFF" d="M25 75h250v187H25z"/><path d="M262.5 12h-225C16.819 12 0 28.824 0 49.51v200.066c0 20.687 16.819 37.51 37.5 37.51h225c20.681 0 37.5-16.823 37.5-37.51V49.51C300 28.824 283.181 12 262.5 12zm0 250.067h-225c-6.9 0-12.506-5.608-12.506-12.51V74.512h250.012v175.045c0 6.902-5.606 12.51-12.506 12.51z" fill="#273141"/><g fill="#374151"><path d="M176.378 216.498c-3.2 0-6.383-1.214-8.816-3.643-4.866-4.857-4.866-12.742 0-17.618l28.62-28.566-28.62-28.566c-4.866-4.857-4.866-12.741 0-17.618 4.867-4.857 12.765-4.857 17.65 0l37.436 37.366c4.866 4.858 4.866 12.742 0 17.618l-37.435 37.365a12.472 12.472 0 01-8.835 3.662zM122.584 116.844c3.2 0 6.383 1.215 8.816 3.643 4.866 4.858 4.866 12.742 0 17.618l-28.62 28.566 28.62 28.566c4.866 4.858 4.866 12.742 0 17.618-4.867 4.858-12.765 4.858-17.65 0L76.313 175.49c-4.866-4.858-4.866-12.742 0-17.618l37.435-37.366a12.472 12.472 0 018.835-3.662z"/></g></g></svg></span>`,
                            "title" : `Code`,
                            "description" : `Insert a peice of code.`,
                            "display" : `block`
                        },
                        "link" : {
                            "icon" : `<span class="p-2.5 border border-gray-200 rounded-lg"><svg class="w-5 h-5 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero"><path d="M76.668 55.129c-21.21 21.944-16.582 58.361 7.713 74.494a2.04 2.04 0 002.554-.246c5.114-4.997 9.441-9.838 13.23-15.995.58-.941.22-2.164-.753-2.69-3.706-2.008-7.394-5.773-9.47-9.75l-.002.002c-2.487-4.951-3.333-10.502-2.017-16.234h.005c1.514-7.339 9.393-14.165 15.41-20.48l-.037-.012 22.547-23.012c8.985-9.17 23.764-9.246 32.842-.168 9.17 8.985 9.322 23.839.337 33.009l-13.656 14.044a2.395 2.395 0 00-.542 2.455c3.145 9.118 3.918 21.975 1.81 31.69-.058.271.277.45.472.25l29.065-29.665c18.568-18.95 18.41-49.805-.35-68.565-19.145-19.146-50.313-18.986-69.261.353L76.784 55.004l-.116.125z"/><path d="M131.005 133.802l-.001.003.054-.023c5.932-10.847 7.1-23.287 4.32-35.414l-.013.013-.014-.006c-2.64-10.801-9.882-21.527-19.72-28.13-.845-.568-2.197-.502-2.99.138-4.982 4.03-9.86 9.196-13.078 15.789a2.188 2.188 0 00.87 2.857c3.734 2.168 7.107 5.343 9.366 9.557l.003-.002c1.76 2.977 3.494 8.626 2.371 14.696h-.002c-1.049 8.048-9.175 15.43-15.636 22.082l.003.003c-4.917 5.029-17.419 17.773-22.424 22.887-8.985 9.17-23.839 9.322-33.009.337-9.17-8.985-9.322-23.838-.337-33.009l13.697-14.085c.62-.639.832-1.566.556-2.413-3.041-9.332-3.875-21.899-1.955-31.602.053-.27-.28-.443-.472-.246l-28.629 29.22c-18.758 19.144-18.599 50.315.354 69.268 19.144 18.757 50.154 18.44 68.912-.704 6.516-7.29 34.41-33.116 37.774-41.216z"/></g></svg></span>`,
                            "title" : `Link`,
                            "description" : `Insert a link.`,
                            "display" : `inline`
                        },
                        "divider" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="w-6 h-6 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero"><path d="M191.666 91.667H8.333a8.333 8.333 0 100 16.667h183.334a8.333 8.333 0 10-.001-16.667z"/><path d="M182.5 45.833h-165a7.5 7.5 0 100 15h165a7.5 7.5 0 100-15z" opacity=".2"/><path d="M182.5 0h-165a7.5 7.5 0 100 15h165a7.5 7.5 0 100-15z" opacity=".1"/><path d="M182.5 139.167h-165a7.5 7.5 0 100 15h165a7.5 7.5 0 100-15z" opacity=".2"/><path d="M182.5 185h-165a7.5 7.5 0 100 15h165a7.5 7.5 0 100-15z" opacity=".1"/></g></svg></span>`,
                            "title" : `Divider`,
                            "description" : `Insert a divider line.`,
                            "display" : `block`
                        },
                        "bulleted_list" : {
                            "icon" : `<span class="p-2.5 border border-gray-200 rounded-lg"><svg class="w-5 h-5 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path d="M20.69 24C9.262 24 0 33.262 0 44.69c0 11.427 9.262 20.69 20.69 20.69 11.427 0 20.69-9.263 20.69-20.69C41.38 33.262 32.116 24 20.69 24zm0 55.172C9.262 79.172 0 88.435 0 99.862c0 11.428 9.262 20.69 20.69 20.69 11.427 0 20.69-9.262 20.69-20.69 0-11.427-9.263-20.69-20.69-20.69zm0 55.173c-11.428 0-20.69 9.262-20.69 20.69 0 11.427 9.262 20.69 20.69 20.69 11.427 0 20.69-9.263 20.69-20.69 0-11.428-9.263-20.69-20.69-20.69zm55.172-75.862h110.345c7.62 0 13.793-6.173 13.793-13.793 0-7.621-6.173-13.794-13.793-13.794H75.862c-7.62 0-13.793 6.173-13.793 13.794 0 7.62 6.173 13.793 13.793 13.793zm110.345 27.586H75.862c-7.62 0-13.793 6.173-13.793 13.793 0 7.62 6.173 13.793 13.793 13.793h110.345c7.62 0 13.793-6.172 13.793-13.793 0-7.62-6.173-13.793-13.793-13.793zm0 55.173H75.862c-7.62 0-13.793 6.172-13.793 13.792s6.173 13.793 13.793 13.793h110.345c7.62 0 13.793-6.172 13.793-13.793 0-7.62-6.173-13.792-13.793-13.792z" fill-rule="nonzero"/></svg></span>`,
                            "title" : `Bulleted List`,
                            "description" : `Add a bulleted list.`,
                            "display" : `block`
                        },
                        "numbered_list" : {
                            "icon" : `<span class="p-2.5 border border-gray-200 rounded-lg"><svg class="w-5 h-5 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero"><path d="M19.565 73.566H6.522A6.524 6.524 0 000 80.087c0 3.6 2.922 6.522 6.522 6.522h13.043c1.2 0 2.174.974 2.174 2.173v2.175a2.174 2.174 0 01-2.174 2.174h-4.348C6.826 93.13 0 99.957 0 108.348v10.87c0 3.6 2.922 6.521 6.522 6.521H28.26c3.6 0 6.522-2.921 6.522-6.521s-2.922-6.522-6.522-6.522H13.043v-4.348c0-1.2.975-2.174 2.174-2.174h4.348c8.391 0 15.217-6.826 15.217-15.217v-2.175c0-8.39-6.826-15.216-15.217-15.216zM19.565 143.131H6.522A6.524 6.524 0 000 149.653c0 3.6 2.922 6.522 6.522 6.522h13.043c1.2 0 2.174.972 2.174 2.173v2.173c0 1.2-.973 2.175-2.174 2.175H10.87a6.524 6.524 0 00-6.522 6.522c0 3.6 2.922 6.522 6.522 6.522h8.695c1.2 0 2.174.973 2.174 2.173v2.173c0 1.201-.973 2.175-2.174 2.175H6.522A6.524 6.524 0 000 188.783c0 3.6 2.922 6.522 6.522 6.522h13.043c8.391 0 15.217-6.826 15.217-15.219v-2.173c0-3.235-1.035-6.225-2.764-8.695 1.729-2.47 2.764-5.461 2.764-8.697v-2.173c0-8.391-6.826-15.217-15.217-15.217zM6.522 17.043h6.521v32.61c0 3.6 2.922 6.52 6.522 6.52s6.522-2.92 6.522-6.52V10.521c0-3.6-2.922-6.522-6.522-6.522H6.522A6.524 6.524 0 000 10.522c0 3.6 2.922 6.521 6.522 6.521zM60.869 38.784h130.434A8.69 8.69 0 00200 30.087a8.688 8.688 0 00-8.697-8.695H60.87a8.687 8.687 0 00-8.695 8.695 8.688 8.688 0 008.695 8.697zM191.303 90.957H60.87a8.687 8.687 0 00-8.695 8.695 8.687 8.687 0 008.695 8.696h130.434A8.688 8.688 0 00200 99.652a8.688 8.688 0 00-8.697-8.695zM191.303 160.521H60.87a8.688 8.688 0 00-8.695 8.697 8.687 8.687 0 008.695 8.695h130.434a8.688 8.688 0 008.697-8.695 8.69 8.69 0 00-8.697-8.697z"/></g></svg></span>`,
                            "title" : `Numbered List`,
                            "description" : `Add a numbered list.`,
                            "display" : `block`
                        },
                        "quote" : {
                            "icon" : `<span class="p-3 border border-gray-200 rounded-lg"><svg class="w-4 h-4 text-gray-700 fill-current" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero"><path d="M200 99.714V14h-85.714v85.714h57.142c0 31.508-25.635 57.143-57.142 57.143v28.572c47.265 0 85.714-38.449 85.714-85.715zM0 156.857v28.572c47.266 0 85.714-38.449 85.714-85.715V14H0v85.714h57.143c0 31.508-25.635 57.143-57.143 57.143z"/></g></svg></span>`,
                            "title" : `Quote`,
                            "description" : `Insert a quote.`,
                            "display" : `block`
                        },
                        "giphy" : {
                            "icon" : `<span class="p-2.5 border border-gray-200 rounded-lg"><svg class="w-5 h-5" viewBox="0 0 201 246" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path fill="#000" d="M29.311 28.387h142.88v189.069H29.3z"/><path fill="#04FF8E" d="M.745 21.377h28.566v203.089H.745z"/><path fill="#8E2EFF" d="M172.179 77.407h28.566v147.059H172.18z"/><path fill="#00C5FF" d="M.745 217.456h200v28.015h-200z"/><path fill="#FFF152" d="M.745.373h114.29v28.014H.744z"/><path fill="#FF5B5B" d="M172.179 56.39V28.387H143.6V.373h-28.566v84.031h85.711V56.39"/><path fill="#551C99" d="M172.179 112.42V84.403h28.566"/><path fill="#999131" d="M115.034.373v28.014H86.456"/></g></svg></span>`,
                            "title" : `Giphy`,
                            "description" : `Add an animated GIF`,
                            "display" : `block`
                        },
                        "codepen" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><circle fill="#374151" cx="100" cy="100" r="100"/><path d="M42.275 122.561l54.845 36.57c1.87 1.15 3.87 1.165 5.76 0l54.845-36.57c1.405-.935 2.275-2.61 2.275-4.285v-36.56c0-1.675-.87-3.35-2.275-4.285L102.88 40.866c-1.87-1.15-3.87-1.16-5.76 0L42.275 77.431C40.87 78.366 40 80.041 40 81.716v36.56c0 1.675.87 3.35 2.275 4.285zm52.57 22.64l-40.38-26.92 18.015-12.055 22.365 14.935v24.04zm10.31 0v-24.04l22.365-14.935 18.015 12.055-40.38 26.92zm44.535-36.57l-12.925-8.635 12.925-8.64v17.275zm-44.535-53.835l40.38 26.92-18.015 12.055-22.365-14.935v-24.04zM100 87.806l18.215 12.19L100 112.186l-18.215-12.19L100 87.806zm-5.155-33.01v24.04L72.48 93.771 54.465 81.716l40.38-26.92zm-44.53 36.57v-.005l12.925 8.64-12.925 8.64V91.366z" fill="#FFF" fill-rule="nonzero"/></g></svg></span>`,
                            "title" : `Codepen`,
                            "description" : `Embed a Codepen.`,
                            "display" : `block`
                        },
                        "codesandbox": {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path d="M13 50l87.125-50 87.125 50 .75 99.583L100.125 200 13 150V50zm17.4 20.675v39.642l27.875 15.5v29.3l33.1 19.133v-68.933L30.4 70.675zm139.492 0l-60.975 34.642v68.933l33.1-19.133v-29.284l27.875-15.508V70.667v.008zM39.117 55.008l60.858 34.534 61-34.834L128.717 36.4 100.3 52.608l-28.583-16.4L39.108 55l.009.008z" fill-rule="nonzero"/></svg></span>`,
                            "title" : `Codesandbox`,
                            "description" : `Embed a Codesandbox.`,
                            "display" : `block`
                        },
                        "youtube" : {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path d="M186.291 56.72c-2.075-7.71-8.154-13.789-15.864-15.865C156.341 37 99.998 37 99.998 37s-56.342 0-70.427 3.708c-7.562 2.075-13.79 8.303-15.865 16.012C10 70.805 10 100.015 10 100.015s0 29.356 3.706 43.294c2.077 7.71 8.155 13.789 15.866 15.865C43.805 163.03 100 163.03 100 163.03s56.341 0 70.427-3.708c7.711-2.075 13.79-8.154 15.866-15.864C190 129.371 190 100.163 190 100.163s.148-29.358-3.709-43.443z" fill="red"/><path fill="#FFF" d="M82.059 127l46.852-26.985L82.06 73.03z"/></g></svg></span>`,
                            "title" : `Youtube`,
                            "description" : `Embed a Youtube Video.`,
                            "display" : `block`
                        },
                        "buy_me_a_coffee": {
                            "icon" : `<span class="p-2 border border-gray-200 rounded-lg"><svg class="text-gray-700 fill-current w-7 h-7" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><g fill-rule="nonzero" fill="none"><path d="M151.05 50.05l-.03-.045-.07-.055c.028.06.063.096.1.1zM153.049 62.986l-.098.028.098-.028zM152.019 49.996a.177.177 0 01-.037-.008v.024a.074.074 0 00.037-.016z" fill="#0D0C22"/><path fill="#0D0C22" d="M151.993 50.004h.014v-.008zM152.95 63.05l.058-.05.022-.018.02-.032a.363.363 0 00-.1.1z"/><path d="M152.05 50.05l-.06-.072-.04-.028a.182.182 0 00.1.1zM98.05 180.95a.278.278 0 00-.1.1l.031-.026c.021-.025.05-.054.069-.074zM129.025 174.16c0-.305-.063-.249-.048.84 0-.087.016-.176.023-.26.008-.195.015-.385.025-.58zM126.05 180.95a.278.278 0 00-.1.1l.031-.026c.021-.025.05-.054.069-.074zM76.05 182.05c-.028-.052-.063-.087-.1-.1.03.031.06.062.08.086l.02.014zM71.05 177a7.098 7.098 0 00-.1-1c.039.32.071.65.097.982l.003.018z" fill="#0D0C22"/><path d="M104.815 92.796c-6.785 2.887-14.485 6.16-24.465 6.16A46.579 46.579 0 0168 97.265l6.902 70.432a11.74 11.74 0 003.78 7.689c2.186 2 5.05 3.11 8.022 3.11 0 0 9.786.505 13.051.505 3.515 0 14.053-.505 14.053-.505a11.88 11.88 0 008.02-3.112c2.186-2 3.535-4.744 3.78-7.687L133 89.866c-3.304-1.12-6.638-1.866-10.396-1.866-6.5-.002-11.738 2.223-17.789 4.796z" fill="#FD0"/><path d="M46.95 62.95l.06.07.04.03a.655.655 0 00-.1-.1z" fill="#0D0C22"/><path d="M164.42 56.248l-1.035-5.217c-.93-4.682-3.038-9.105-7.849-10.797-1.542-.542-3.291-.774-4.473-1.895-1.183-1.12-1.532-2.86-1.806-4.474-.506-2.962-.982-5.926-1.501-8.882-.448-2.542-.802-5.398-1.97-7.73-1.519-3.13-4.67-4.962-7.805-6.173a44.985 44.985 0 00-4.91-1.518c-7.83-2.063-16.063-2.822-24.12-3.255a202.712 202.712 0 00-29.011.48c-7.18.653-14.742 1.442-21.566 3.924-2.493.908-5.063 1.998-6.96 3.922-2.326 2.365-3.086 6.022-1.387 8.971 1.208 2.094 3.254 3.574 5.423 4.553a44.02 44.02 0 008.806 2.863c8.43 1.861 17.163 2.592 25.776 2.903 9.546.385 19.108.073 28.609-.933 2.35-.258 4.695-.567 7.036-.928 2.757-.423 4.526-4.024 3.714-6.533-.973-3-3.585-4.163-6.54-3.71-.435.068-.868.131-1.304.194l-.314.046a159.522 159.522 0 01-9.22.9c-4.654.324-9.32.473-13.983.48-4.583 0-9.168-.128-13.74-.43-2.087-.136-4.168-.31-6.244-.52-.944-.099-1.886-.202-2.828-.319l-.896-.114-.195-.027-.929-.134a97.99 97.99 0 01-5.676-1.012.853.853 0 010-1.664h.035a92.215 92.215 0 014.914-.9c.549-.087 1.1-.171 1.651-.254h.015c1.03-.068 2.066-.253 3.092-.374a196.808 196.808 0 0126.86-.946c4.351.127 8.7.382 13.033.822.931.096 1.858.197 2.785.311.354.043.711.094 1.068.137l.719.103c2.096.312 4.182.69 6.256 1.136 3.074.668 7.02.885 8.388 4.25.435 1.066.633 2.253.873 3.373l.307 1.429c.008.026 1.893 10.169 1.897 10.195.724 3.372-.43-3.372.295 0a1.851 1.851 0 01-1.56 2.223h-.02l-.443.06-.438.059a269.435 269.435 0 01-12.398 1.315c-5.46.454-10.931.751-16.413.893-2.794.074-5.586.109-8.378.104a291.007 291.007 0 01-33.252-1.933c-1.195-.141-2.39-.293-3.585-.447.926.119-.674-.091-.998-.137-.76-.106-1.519-.216-2.278-.331-2.55-.382-5.084-.852-7.629-1.265-3.076-.506-6.018-.253-8.8 1.265a12.793 12.793 0 00-5.299 5.488c-1.2 2.479-1.557 5.177-2.094 7.84-.536 2.664-1.372 5.53-1.055 8.263.68 5.901 4.81 10.696 10.75 11.769 5.587 1.011 11.205 1.83 16.838 2.529a311.492 311.492 0 0072.097.415 3.802 3.802 0 013.991 2.493c.19.533.26 1.102.202 1.664l-.562 5.458-3.398 33.084a26261.22 26261.22 0 01-4.58 44.519c-.324 3.21-.37 6.52-.98 9.692-.962 4.987-4.341 8.05-9.273 9.17a64.683 64.683 0 01-13.768 1.609c-5.137.028-10.271-.2-15.408-.172-5.484.03-12.2-.475-16.434-4.552-3.72-3.581-4.233-9.189-4.74-14.038-.675-6.419-1.344-12.837-2.007-19.254L64.076 110.6l-2.408-23.089c-.04-.382-.08-.758-.119-1.143-.288-2.754-2.24-5.45-5.316-5.311-2.633.116-5.626 2.352-5.317 5.311l1.785 17.118 3.691 35.408c1.052 10.057 2.1 20.116 3.147 30.178.203 1.927.393 3.86.605 5.787 1.157 10.531 9.208 16.207 19.178 17.804 5.823.937 11.788 1.13 17.697 1.225 7.575.122 15.227.413 22.677-.958 11.042-2.025 19.325-9.389 20.508-20.813l1.013-9.897c1.122-10.912 2.243-21.826 3.362-32.74l3.66-35.661 1.68-16.343a3.79 3.79 0 013.058-3.337c3.157-.614 6.175-1.664 8.42-4.064 3.575-3.821 4.287-8.804 3.023-13.827zM45.657 59.774c.049-.023-.04.39-.078.582-.008-.291.008-.55.078-.582zm.307 2.367c.025-.018.101.084.18.205-.12-.111-.195-.195-.183-.205h.003zm.301.397c.109.185.167.301 0 0zm.605.49h.015c0 .019.028.036.038.054a.392.392 0 00-.055-.053h.002zm105.96-.733c-1.134 1.078-2.843 1.579-4.532 1.83-18.938 2.806-38.151 4.228-57.297 3.6-13.702-.467-27.26-1.987-40.824-3.902-1.33-.187-2.77-.43-3.684-1.409-1.722-1.846-.876-5.564-.428-7.795.41-2.043 1.195-4.767 3.628-5.058 3.798-.445 8.208 1.156 11.965 1.725a226.79 226.79 0 0013.621 1.657c19.45 1.77 39.225 1.494 58.588-1.095 3.529-.474 7.046-1.025 10.55-1.652 3.121-.56 6.582-1.609 8.469 1.621 1.293 2.2 1.465 5.144 1.265 7.63a4.252 4.252 0 01-1.324 2.848h.003z" fill="#0D0C22"/></g></svg></span>`,
                            "title" : `Buy Me a Coffee`,
                            "description" : `Buy Me a Coffee Link.`,
                            "display" : `block`
                        }
                    }
                    let dropdownItemsConfig = ('{{ implode(',', config("markdownx.dropdown_items")) }}').split(',');
                    for (const [key, value] of Object.entries(dropdownItems)) {
                        if(!dropdownItemsConfig.includes(key)){
                            delete dropdownItems[key];
                        }
                    }

                    return dropdownItems;
                },
                initializeAceEditor () {
                    this.codeEditor = ace.edit(document.getElementById('editor-code'), {
                        maxLines: 50,
                        minLines: 10,
                        fontSize: 18
                    });
                    this.codeEditor.getSession().setUseWorker(false);
                    this.codeEditor.setOptions({
                        autoScrollEditorIntoView: true,
                        copyWithEmptySelection: true,
                    });

                    this.codeEditor.focus();
                },
                getYoutubeIDFromURL (url) {
                    url = url.split(/(vi\/|v=|\/v\/|youtu\.be\/|\/embed\/)/);
                    return (url[2] !== undefined) ? url[2].split(/[^0-9a-z_\-]/i)[0] : url[0];
                },
                createDropDownElement () {
                    const marker = document.createElement("div");
                    marker.className = 'absolute z-50 block w-auto overflow-hidden transition-all duration-100 ease-in-out transform scale-95 translate-x-0 bg-white border border-gray-200 rounded-md shadow-xl translate-y-7 whitespace-wrap';
                    return marker;
                },
                isNormalInteger (str) {
                    str = str.trim();
                    if (!str) {
                        return false;
                    }
                    str = str.replace(/^0+/, "") || "0";
                    var n = Math.floor(Number(str));
                    return n !== Infinity && String(n) === str && n >= 0;
                },
                setModalHTML (suggestion) {
                    this.popupType = suggestion;
                    let modalSuggestionHTML = [];

                    modalSuggestionHTML["link"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Link Text
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-link-text" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Text">
                        <label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
                            Link URL
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-link-url" class="block w-full transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="URL">`;
                    modalSuggestionHTML["code"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Code
                        </label>
                        <div id="editor-code" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5"></div>`;
                    modalSuggestionHTML["giphy"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Insert an animated GIF
                        </label>
                        <input type="text" @keydown="searchGIFModal(event)" id="editor-giphy-search" class="block w-full transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Search for a GIF">
                        <div id="giphy-items" class="grid w-full h-64 grid-cols-3 gap-1 p-1 mt-2 overflow-y-scroll border border-gray-200 rounded-lg bg-gray-50 grid-cols">
                            <div class="absolute inset-0 flex items-center justify-center w-full h-full">
                                <svg class="w-5 h-5 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>`;
                    modalSuggestionHTML["codepen"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Codepen URL
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-codepen-url" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Codepen URL">`;
                    modalSuggestionHTML["codesandbox"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Codesandbox URL
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-codesandbox-url" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Codesandbox URL">`;
                    modalSuggestionHTML["youtube"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Youtube URL
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-youtube-url" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Youtube URL">`;
                    modalSuggestionHTML["buy_me_a_coffee"] = `<label for="text" class="block mb-1.5 text-sm font-medium leading-5 text-gray-700 sm:mt-px">
                            Buy Me A Coffee Username
                        </label>
                        <input type="text" @keydown="submitModal(event)" id="editor-buy-me-a-coffee-username" class="block w-full mb-2 transition duration-150 ease-in-out form-input sm:text-sm sm:leading-5" placeholder="Username">`;

                    this.$refs.editorModalContent.innerHTML = modalSuggestionHTML[suggestion];
                },
                getSuggestionsHTML () {
                    let suggestionHTML = `<span class="relative flex items-center w-full p-3 rounded-lg">
                                            [[ icon ]]
                                            <span class="absolute inset-0 w-full h-full" data-suggestion="[[ key ]]"></span>
                                            <span class="flex flex-col mx-3">
                                                <h4 class="text-xs font-medium mt-0.5 leading-none text-gray-700">[[ title ]]</h4>
                                                <p class="text-xs mt-0.5 text-gray-400">[[ description ]]</p>
                                            </span>
                                        </span>`;
                    let dropdownSuggestions = this.suggestionDropdownItems();
                    let dropdownSuggestionHTML = {};

                    for (const [key, value] of Object.entries(dropdownSuggestions)) {
                        Object.assign(dropdownSuggestionHTML, {[key]: suggestionHTML.replace('[[ icon ]]', value.icon).replace('[[ title ]]', value.title).replace('[[ description ]]', value.description).replace('[[ key ]]', key) });
                    }

                    return dropdownSuggestionHTML;
                },
                submitModal (event) {
                    if (event.keyCode === 13) {
                        event.preventDefault();
                        this.$refs.modalExecute.click();
                    }
                },
                searchGIFModal(event){
                    if (event.keyCode === 13) {
                        event.preventDefault();
                        if(event.target.value.trim() != ""){
                            window.livewire.emit('markdown-x-giphy-search', { search: event.target.value, key: this.$refs.markdownX.dataset.key });
                        }
                    }
                },
                executeAssociatedFunction () {
                    let type = this.$refs.modalExecute.dataset.suggestion;
                    let editor = this.$refs.editor;
                    switch(type){
                        case "link":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let linkText = document.getElementById('editor-link-text').value;
                                let linkURL = document.getElementById('editor-link-url').value;
                                editor.insertAtCaret('[' + linkText + '](' + linkURL + ')');
                            break;
                        case "code":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let newCodeBlock = this.codeEditor.getValue();
                                editor.insertAtCaret('```\n' + newCodeBlock + '\n```\n\n');
                                this.repositionPlaceholder();
                            break;
                        case "codepen":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let codepenURL = document.getElementById('editor-codepen-url').value;
                                editor.insertAtCaret('{% codepen ' + codepenURL + ' %}\n');
                            break;
                        case "codesandbox":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let codesandboxURL = document.getElementById('editor-codesandbox-url').value;
                                editor.insertAtCaret('{% codesandbox ' + codesandboxURL + ' %}\n');
                            break;
                            editor
                        case "youtube":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let youtubeURL = document.getElementById('editor-youtube-url').value;
                                let youtubeID = this.getYoutubeIDFromURL(youtubeURL);
                                editor.insertAtCaret('{% youtube ' + youtubeID + ' %}\n');
                            break;
                        case "buy_me_a_coffee":
                                editor.setCaretPosition(this.editStart, this.editStart);
                                let bmacUsername = document.getElementById('editor-buy-me-a-coffee-username').value;
                                editor.insertAtCaret('{% buymeacoffee ' + bmacUsername + ' %}\n');
                            break;
                        default:
                            break;
                    }
                    this.popup = false;
                    this.togglePlaceholder();
                },
                dispatchEditorEvent(dynamicEventName){
                    let dynamicEvent =  null;
                    for(var i=0; i<this.dynamicEditorEvents.length;i++){
                        if(this.dynamicEditorEvents[i].name == dynamicEventName){
                            dynamicEvent = this.dynamicEditorEvents[i];
                        }
                    }
                    if(dynamicEvent === null){
                        let event = new Event(dynamicEventName);
                        this.$refs.editor.addEventListener(dynamicEventName, function (e) { /* ... */ }, false);
                        dynamicEvent = {
                            name : dynamicEventName,
                            event : event
                        };
                        this.dynamicEditorEvents.push(dynamicEvent);
                    }
                    this.$refs.editor.dispatchEvent(dynamicEvent.event);
                },
                scrollParentToChild (parent, child) {
                    // Where is the parent on page
                    var parentRect = parent.getBoundingClientRect();
                    // What can you see?
                    var parentViewableArea = {
                        height: parent.clientHeight,
                        width: parent.clientWidth
                    };

                    // Where is the child
                    var childRect = child.getBoundingClientRect();
                    // Is the child viewable?
                    var isViewable = (childRect.top >= parentRect.top) && (childRect.bottom <= parentRect.top + parentViewableArea.height);

                    // if you can't see the child try to scroll parent
                    if (!isViewable) {
                            // Should we scroll using top or bottom? Find the smaller ABS adjustment
                            const scrollTop = childRect.top - parentRect.top;
                            const scrollBot = childRect.bottom - parentRect.bottom;
                            if (Math.abs(scrollTop) < Math.abs(scrollBot)) {
                                // we're near the top of the list
                                parent.scrollTop += scrollTop;
                            } else {
                                // we're near the bottom of the list
                                parent.scrollTop += scrollBot;
                            }
                    }

                },
                showModalPop (callback) {
                    let editor = this.$refs.editor;
                    let { paddingRight,  paddingTop, lineHeight } = getComputedStyle(editor);

                    let { x, y } = this.getCursorXY();
                    let newLeft = Math.min(x - editor.scrollLeft, editor.offsetLeft + editor.offsetWidth - parseInt(paddingRight, 10));
                    let newTop = Math.min(y - editor.scrollTop + parseInt(lineHeight), editor.offsetTop + editor.offsetHeight - parseInt(lineHeight, 10));
                    this.$refs.editorModal.setAttribute(
                        "style",
                        `left: ${newLeft}px; top: ${newTop}px`
                    );
                    this.popup = true;
                    callback();
                },
                selectItem (selected) {

                    let newLine = '';
                    // if this is a block level item and the line is not empty we want to add a new line before inserting text
                    if(!this.isCurrentLineEmpty && this.suggestionDropdownItems()[selected.dataset.suggestion]['display'] == "block"){
                        newLine = '\n';
                    }

                    switch (selected.dataset.suggestion) {
                        case 'text':
                            this.replaceSuggestionText('');
                            break;
                        case 'heading':
                            this.replaceSuggestionText(newLine + '# ');
                            break;
                        case 'heading_2':
                            this.replaceSuggestionText(newLine + '## ');
                            break;
                        case 'heading_3':
                            this.replaceSuggestionText(newLine + '### ');
                            break;
                        case 'image':
                            this.$refs.image.click();
                            this.replaceSuggestionText(newLine + '\n');
                            break;
                        case 'code':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="code";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                let that = this;
                                this.showModalPop(function(){
                                    that.initializeAceEditor();
                                    that.codeEditor.focus();
                                });
                            break;
                        case 'link':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="link";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText('');
                                this.showModalPop(function(){
                                    document.getElementById('editor-link-text').focus();
                                });
                            break;
                        case 'divider':
                            this.replaceSuggestionText(newLine + '\n---\n');
                            break;
                        case 'numbered_list':
                            this.replaceSuggestionText(newLine + ' 1. ');
                            break;
                        case 'bulleted_list':
                            this.replaceSuggestionText(newLine + ' - ');
                            break;
                        case 'giphy':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="giphy";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                window.livewire.emit('markdown-x-giphy-load', { key: this.$refs.markdownX.dataset.key });
                                this.showModalPop(function(){
                                    document.getElementById('editor-giphy-search').focus();
                                });
                            break;
                        case 'codepen':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="codepen";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                this.showModalPop(function(){
                                    document.getElementById('editor-codepen-url').focus();
                                });
                            break;
                        case 'codesandbox':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="codesandbox";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                this.showModalPop(function(){
                                    document.getElementById('editor-codesandbox-url').focus();
                                });
                            break;
                        case 'quote':
                                this.replaceSuggestionText(newLine + '> ');
                            break;
                        case 'youtube':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="youtube";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                this.showModalPop(function(){
                                    document.getElementById('editor-youtube-url').focus();
                                });
                            break;
                        case 'buy_me_a_coffee':
                                this.setModalHTML(selected.dataset.suggestion);
                                this.$refs.modalExecute.dataset.suggestion="buy_me_a_coffee";
                                this.$refs.editor.setCaretPosition(this.currentCaretPos.start, this.currentCaretPos.end);
                                this.replaceSuggestionText(newLine + '');
                                this.showModalPop(function(){
                                    document.getElementById('editor-buy-me-a-coffee-username').focus();
                                });
                            break;
                        default:
                            console.log('Warning: Unsure which dropdown item to execute.')
                            break;
                    }
                },
                replaceSuggestionText (content) {
                    let editor = this.$refs.editor;

                    const start = editor.value.slice(0, this.editStart);
                    const end = editor.value.slice(editor.selectionStart, editor.value.length);

                    let updatedText = `${start}${content}${end}`;

                    editor.value = updatedText;
                    this.$refs.editor.setCaretPosition(this.editStart+content.length, this.editStart+content.length);
                },
                clearSuggestionText () {
                    let editor = this.$refs.editor;

                    const start = editor.value.slice(0, this.editStart);
                    const end = editor.value.slice(editor.selectionStart, editor.value.length);

                    window.replaceEditorText(`${start}${end}`, editor);
                    editor.setCaretPosition(this.editStart, this.editStart);
                },
                togglePlaceholder(){
                    this.placeholder=!this.placeholder;
                    this.placeholder=!this.placeholder;
                },
                repositionPlaceholder () {
                    let editor = this.$refs.editor;
                    let { paddingRight, paddingTop, lineHeight, fontSize, fontFamily } = getComputedStyle(editor);
                    const { x, y } = this.getCursorXY();
                    const newLeft = Math.min(
                        x - editor.scrollLeft,
                        editor.offsetLeft + editor.offsetWidth - parseInt(paddingRight, 10)
                    );
                    let newTop = y - editor.scrollTop;
                    this.$refs.placeholder.setAttribute(
                        "style",
                        `left: ${(newLeft) + 5}px; top: ${newTop}px; font-size:${fontSize}; font-family:${fontFamily}; margin-top:-0.2rem;`
                    );
                },
                getCurrentLine () {
                    return this.$refs.editor.value.split(/\r?\n/)[this.currentLineNumber()-1];
                },
                currentLineNumber () {
                    return this.$refs.editor.value.substr(0, this.$refs.editor.selectionStart).split("\n").length;
                },
                getPreviousLine () {
                    let lines = this.$refs.editor.value.split(/\r?\n/);
                    // if we have a previous line, return it
                    if( typeof lines[currentLineNumber()-2] !== 'undefined' ){
                        return lines[currentLineNumber()-2];
                    }
                    return null;
                },
                handleSpaceFunctionality () {
                    if( this.lineIsStartOfNumberedList(this.getCurrentLine()) || this.lineIsStartOfBulletedList( this.getCurrentLine() )){

                        let editor = this.$refs.editor;
                        // add leading space to line
                        let beginning = editor.value.slice(0, editor.selectionStart);
                        let beginningOfLine = editor.value.slice(0, beginning.lastIndexOf('\n') + 1);
                        let endOfLine = editor.value.slice(beginning.lastIndexOf('\n') +1, editor.value.length);

                        window.replaceEditorText(`${beginningOfLine} ${endOfLine}`, editor);
                        this.$refs.editor.setCaretPosition(this.currentCaretPos.start+1, this.currentCaretPos.end+1);
                    }
                },
                listContainsContent (type, string) {
                    if(type == 'numbered'){
                        let numSplit = string.split('.');
                        if( this.isNormalInteger(numSplit[0]) && numSplit[1].trim() !== ""){
                            return true;
                        }
                        return false
                    } else {
                        if( string.trim() == '-' || string.trim() == '+' || string.trim() == '*' ){
                            return false;
                        }
                        return true;
                    }
                },
                lineIsStartOfNumberedList (line) {
                    let numSplit = line.split('.');
                    if( this.isNormalInteger(numSplit[0]) && numSplit[1] == " "){
                        return true;
                    }
                    return false;
                },
                lineIsStartOfBulletedList (line) {
                    if( line.trim() == '-' || line.trim() == '+' || line.trim() == '*' ){
                        return true;
                    }
                    return false;
                },
                lineIsNumberedList (string) {

                    let curListItem = string.trim().split(" ");
                    if(typeof curListItem[0] != "undefined" && typeof curListItem[1] != "undefined"){
                        if( this.lineIsStartOfNumberedList(curListItem[0] + " ") && curListItem[1] != ""){
                            return true;
                        }
                    }
                    return false;
                },
                lineIsBulletedList (string) {
                    let curListItem = string.trim().split(" ");
                    if(typeof curListItem[0] != "undefined" && typeof curListItem[1] != "undefined"){
                        if( this.lineIsStartOfBulletedList(curListItem[0] + " ") && curListItem[1] != ""){
                            return true;
                        }
                    }
                    return false;
                },
                handleLists (e, type) {
                    // detect that it is a keydown type event
                    if(type == 'keydown'){

                        let currentLine = this.getCurrentLine();
                        let editor = this.$refs.editor;

                        let startOfNumberedList = this.lineIsStartOfNumberedList(currentLine);
                        let startOfBulletedList = this.lineIsStartOfBulletedList(currentLine);
                        let numberedList = this.lineIsNumberedList(currentLine);
                        let bulletedList = this.lineIsBulletedList(currentLine);
                        if(numberedList || bulletedList){
                            let listType = 'numbered';
                            if(bulletedList){
                                listType = 'bulleted';
                            }
                            this.handleNextLineInList(listType, currentLine);
                            e.preventDefault();
                        } else if( startOfNumberedList || startOfBulletedList ){
                                e.preventDefault();
                                // remove content from the current line
                                let beginning = editor.value.slice(0, editor.selectionStart);
                                let lastPos = beginning.lastIndexOf('\n') + 1;
                                let beginningOfLine = editor.value.slice(0, lastPos);
                                let endOfLine = editor.value.slice(editor.selectionStart, editor.value.length);
                                window.replaceEditorText(`${beginningOfLine}\n${endOfLine}`, editor);
                                this.$refs.editor.setCaretPosition(lastPos+1, lastPos+1);
                        }
                    }

                },
                handleNextLineInList (type, string) {
                    // detect next line list item
                    let curListItem = string.trim().split(" ");
                    // offset 0 is our list item
                    if(typeof curListItem[0] != "undefined"){
                        let nextItem = curListItem[0];
                        if(type == 'numbered'){
                            // handle numbered list
                            nextItem = (parseInt(curListItem[0].replace('.', '')) + 1).toString() + '.';
                        }
                        // Insert nextItem on next line.
                        this.$refs.editor.insertAtCaret('\n ' + nextItem + ' ');
                    }
                },
                /** HERE OUT *****/
                toggleItem (dir = "next") {
                    let editor = this.$refs.editor;
                    const list = this.dropdownEl.querySelector("ul");
                    if (!editor.__SELECTED_ITEM) {
                        editor.__SELECTED_ITEM = this.dropdownEl.querySelector("li");
                        editor.__SELECTED_ITEM.className = suggestionActiveClasses;
                    } else {
                        editor.__SELECTED_ITEM.className = '';
                        let nextActive = editor.__SELECTED_ITEM[`${dir}ElementSibling`];
                        if (!nextActive && dir === "next") nextActive = list.firstChild;
                        else if (!nextActive) nextActive = list.lastChild;
                        editor.__SELECTED_ITEM = nextActive;
                        nextActive.className = suggestionActiveClasses;
                    }
                    this.scrollParentToChild(list, editor.__SELECTED_ITEM);
                },
                filterList () {
                    let value = this.$refs.editor.value;
                    let editor = this.$refs.editor;
                    const filter = value
                        .slice(this.editStart + 1, this.$refs.editor.selectionStart)
                        .toLowerCase();
                    const suggestionHTML = this.getSuggestionsHTML();
                    const suggestions = Object.keys(suggestionHTML);
                    const filteredSuggestions = suggestions.filter(function(entry){
                        return entry.replaceAll('_', ' ').includes(filter);
                    });
                    if (!filteredSuggestions.length){
                        filteredSuggestions.push('none');
                        suggestionHTML['none'] = '<div class="px-4 py-2 text-sm font-medium text-gray-500">No suggestions found.</div>';
                    }
                    const suggestedList = document.createElement("ul");
                    suggestedList.className = suggestionClasses;
                    suggestedList.style.maxHeight = '330px';
                    filteredSuggestions.forEach((entry) => {
                        const entryItem = document.createElement("li");
                        entryItem.dataset.suggestion = entry;
                        entryItem.className = suggestionItemClasses;
                        entryItem.innerHTML = suggestionHTML[entry];
                        suggestedList.appendChild(entryItem);
                    });
                    if (this.dropdownEl.firstChild)
                        this.dropdownEl.replaceChild(
                            suggestedList,
                            this.dropdownEl.firstChild
                        );
                    else this.dropdownEl.appendChild(suggestedList);

                    this.setFirstSuggestionActive();
                },
                setFirstSuggestionActive () {
                    let editor = this.$refs.editor;
                    editor.__SELECTED_ITEM = this.dropdownEl.querySelector("li:first-child");
                    editor.__SELECTED_ITEM.className = suggestionActiveClasses;
                },
                clickItem (e) {
                    e.preventDefault();
                    if (e.target.dataset.suggestion) {
                        this.$refs.editor.focus();
                        this.clearSuggestionText();
                        this.toggleSuggestionDropdown();
                        let that = this;
                        setTimeout(function(){
                            that.selectItem(e.target);
                        }, 1);
                    }
                },
                // toggle custom UI on and off
                toggleSuggestionDropdown () {
                    let editor = this.$refs.editor;
                    this.editStart = editor.selectionStart;
                    this.suggestionDropdown = !this.suggestionDropdown;

                    const { paddingRight, lineHeight } = getComputedStyle(editor);

                    if (this.suggestionDropdown && !this.dropdownEl && document.activeElement == this.$refs.editor) {
                        this.showSuggestionDropdown();
                    } else {
                        this.hideSuggestionDropdown();
                    }

                    if (this.suggestionDropdown) {
                        // update list to show
                        this.filterList();
                        // update position
                        const { x, y } = this.getCursorXY();
                        const newLeft = Math.min(
                            x - editor.scrollLeft,
                            editor.offsetLeft + editor.offsetWidth - parseInt(paddingRight, 10)
                        );
                        const newTop = Math.min(
                            y - editor.scrollTop,
                            editor.offsetTop + editor.offsetHeight - parseInt(lineHeight, 10)
                        );
                        this.dropdownEl.setAttribute(
                            "style",
                            `left: ${newLeft}px; top: ${newTop}px`
                        );

                    }
                },

                showSuggestionDropdown () {
                    let editor = this.$refs.editor;
                    // if we are showing it, we want to tell if this current line is empty or not
                    this.isCurrentLineEmpty = true;
                    let curLine = this.getCurrentLine();
                    if(curLine.trimRight('/') !== ""){
                        this.isCurrentLineEmpty = false;
                    }

                    this.suggestionDropdown = true;
                    // assign a created marker to input
                    this.dropdownEl = this.createDropDownElement();
                    // append it to the body
                    this.$refs.dropdown.appendChild(this.dropdownEl);
                    let that = this;
                    setTimeout(function(){
                        that.dropdownEl.classList.remove('scale-95');
                        that.dropdownEl.classList.add('scale-100');
                        that.dropdownEl.classList.remove('translate-y-7');
                        that.dropdownEl.classList.add('translate-y-9');
                    }, 1);
                },
                hideSuggestionDropdown () {
                    let editor = this.$refs.editor;
                    this.suggestionDropdown = false;
                    if(this.dropdownEl){
                        this.$refs.dropdown.removeChild(this.dropdownEl);
                    }
                    this.dropdownEl = null;
                },
                droppingFile(e){
                    e.preventDefault();
                    this.dropFiles = false;
                    let dataTransfer = new DataTransfer();
                    dataTransfer.items.add(e.dataTransfer.files[0]);
                    this.$refs.image.files = dataTransfer.files;
                    this.$refs.image.dispatchEvent(new Event('change'));
                },
                editorEvent (e) {

                    let editor = e.target;
                    let which = 0;
                    if(typeof e.which != "undefined" || typeof e.keyCode != "undefined"){
                        which = e.which || e.keyCode || 0;
                    }
                    let type = e.type;
                    const {
                        offsetHeight,
                        offsetLeft,
                        offsetTop,
                        offsetWidth,
                        scrollLeft,
                        scrollTop,
                        selectionStart,
                        value
                    } = editor;
                    const { paddingRight, lineHeight } = getComputedStyle(editor);

                    const previousChar = editor.value.charAt(editor.selectionStart - 1).trim();

                    if(which == 13){
                        this.handleLists(e, type);
                    }

                    if(which == 32 && type == "keyup"){
                        this.handleSpaceFunctionality();
                    }

                    if(which == 13 && type == "keyup"){

                    } else {
                        this.popup = false;
                    }

                    if(this.getCurrentLine() == "" && (type == "keyup" || type == "focus" || type == 'click')){
                        this.repositionPlaceholder();
                        this.placeholder = true;
                    } else {
                        this.placeholder = false;
                    }

                    if ((which === 191 && previousChar === "")) {
                        this.toggleSuggestionDropdown();
                    } else if (this.suggestionDropdown) {
                        switch (which) {

                            case 35:
                                break;
                            case 27:
                                this.hideSuggestionDropdown();
                                break;
                            // Backspace or delete key
                            case 8:
                                if (editor.selectionStart <= this.editStart){
                                    this.toggleSuggestionDropdown();
                                }
                                else this.filterList();
                                break;
                            case 13:
                                if (editor.__SELECTED_ITEM) {
                                    e.preventDefault();
                                    this.selectItem( this.dropdownEl.querySelector(".suggestion-active") );
                                    this.hideSuggestionDropdown();
                                } else {
                                    this.toggleSuggestionDropdown();
                                }
                                break;
                            case 38:
                            case 40:
                                if (type === "keydown") {
                                    e.preventDefault();
                                    // up is 38
                                    this.toggleItem(which === 38 ? "previous" : "next");
                                    // down is 40
                                }
                                break;
                            case 37:
                            case 39:
                                if (editor.selectionStart < this.editStart + 1) this.toggleSuggestionDropdown();
                                break;
                            default:
                                this.filterList();
                                break;
                        }
                    }
                }
            }
        }


        window.loadDynamicScript = function (url, id) {
            let existingScript = document.getElementById(id);

            if (!existingScript) {
                const script = document.createElement('script');
                script.src = url; // URL for the third-party library being loaded.
                script.id = id; // e.g., googleMaps or stripe
                document.body.appendChild(script);
            }
        }

        window.loadDynamicScript('https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.min.js', 'ace-library');

        window.addEventListener('markdown-x-image-uploaded', event => {
            let editor = document.getElementById('editor-' + event.detail.key);
            let fileInput = document.getElementById('image-' + event.detail.key);
            if(parseInt(event.detail.status) != 200){
                showErrorMessage(document.getElementById('error-' + event.detail.key), event.detail.message);
                replaceEditorText(editor.value.replace(event.detail.text, ""), editor);
                return;
            }
            let imageText = "![" + event.detail.name + "](" + event.detail.path + ")\r";
            setTimeout(function(){
                replaceEditorText(editor.value.replace(event.detail.text, "![" + event.detail.name + "](" + event.detail.path + ")\r"), editor);
            }, 10);

            // clear file input
            fileInput.value = '';
            editor.focus();
        });

        window.addEventListener('markdown-x-giphy-results', event => {
            let giphyResults = event.detail.results;

            let giphyContents = `<div class="space-y-1">`;
            for(var i=0; i<giphyResults.length; i++){
                giphyContents += `<img src="${giphyResults[i]['image']}" onclick="addAnimatedGif('{% giphy ${giphyResults[i]['embed']} %}', '${event.detail.key}')" class="w-full h-auto border-2 border-gray-200 rounded cursor-pointer hover:border-blue-500" />`;
                if(i%10 == 0 && i != 0){
                    giphyContents += `</div><div class="space-y-1">`;
                }
            }
            giphyContents += `</div>`;
            document.getElementById('giphy-items').innerHTML = giphyContents;
        });

        window.addAnimatedGif = function(content, key){
            let editor = document.getElementById('editor-' + key);
            let insertionLocationEl = document.getElementById('markdownx-insert-' + key);
            let insertionLocation = parseInt(insertionLocationEl.dataset.insert);

            let start = editor.value.slice(0, insertionLocation);
            content += '\n';
            let end = editor.value.slice(insertionLocation, editor.value.length);
            replaceEditorText(`${start}${content}${end}`, editor);
            editor.setCaretPosition(insertionLocation+content.length, insertionLocation+content.length);

            editor.blur();
            editor.focus();
        }

        window.replaceEditorText = function(updatedText, editor){
            // Doing it this way as opposted to setting editor.value of textarea will preserve undo/redo
            editor.focus();
            document.execCommand('selectAll',false);

            let tempEl = document.createElement('p');
            tempEl.innerText = updatedText;

            document.execCommand('insertHTML', false, tempEl.innerHTML);
        }

        window.showErrorMessage = function(el, message){
            el.classList.remove('hidden');
            el.innerText = message;
            setTimeout(function(){
                el.classList.add('hidden');
                el.innerText = '';
            }, 3000);
        }

        /********** TEXT AREA PROTOTYPES **********/
        HTMLTextAreaElement.prototype.insertAtCaret = function(text) {
            text = text || '';
            if (document.selection) {
                // IE
                this.focus();
                var sel = document.selection.createRange();
                sel.text = text;
            } else if (this.selectionStart || this.selectionStart === 0) {
                // Others
                var startPos = this.selectionStart;
                var endPos = this.selectionEnd;

                let updatedText = this.value.substring(0, startPos) +
                    text +
                    this.value.substring(endPos, this.value.length);
                window.replaceEditorText(updatedText, this);


                this.selectionStart = startPos + text.length;
                this.selectionEnd = startPos + text.length;
            } else {
                window.replaceEditorText(this.value += text, this);
            }
        };

        HTMLTextAreaElement.prototype.getCaretPosition = function() {
            // IE < 9 Support
            if (document.selection) {
                this.focus();
                var range = document.selection.createRange();
                var rangelen = range.text.length;
                range.moveStart('character', -el.value.length);
                var start = range.text.length - rangelen;
                return {
                    'start': start,
                    'end': start + rangelen
                };
            } // IE >=9 and other browsers
            else if (this.selectionStart || this.selectionStart == '0') {
                return {
                    'start': this.selectionStart,
                    'end': this.selectionEnd
                };
            } else {
                return {
                    'start': 0,
                    'end': 0
                };
            }
        };

        HTMLTextAreaElement.prototype.setCaretPosition =  function(start, end) {
            // IE >= 9 and other browsers
            if (this.setSelectionRange) {
                this.focus();
                this.setSelectionRange(start, end);
            }
            // IE < 9
            else if (this.createTextRange) {
                var range = this.createTextRange();
                range.collapse(true);
                range.moveEnd('character', end);
                range.moveStart('character', start);
                range.select();
            }
        };
        /********** END TEXT AREA PROTOTYPES **********/

    </script>
</div>
