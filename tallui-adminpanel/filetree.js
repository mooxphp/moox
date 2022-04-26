let fileTree = function() {
    return {
        levels: [
            {
                title: 'AlphineJS',
                children: [
                    {
                        title: 'LICENSE.md',
                    },
                    {
                        title: 'README.ja.md',
                    },
                    {
                        title: 'README.md',
                    },
                    {
                        title: 'README.ru.md',
                    },
                    {
                        title: 'README_zh-TW.md',
                    },
                    {
                        title: 'babel.config.js',
                    },
                    {
                        title: 'dist/',
                        children: [
                            {
                                title: 'alpine-ie11.js',
                            },
                            {
                                title: 'alpine.js',
                            },
                        ],
                    },
                    {
                        title: 'examples/',
                        children: [
                            {
                                title: 'card-game.html',
                            },
                            {
                                title: 'index.html',
                            },
                            {
                                title: 'tags.html',
                            },
                        ],
                    },
                    {
                        title: 'jest.config.js',
                    },
                    {
                        title: 'package-lock.json',
                    },
                    {
                        title: 'package.json',
                    },
                    {
                        title: 'rollup-ie11.config.js',
                    },
                    {
                        title: 'rollup.config.js',
                    },
                    {
                        title: 'src/',
                        children: [
                            {
                                title: 'component.js',
                            },
                            {
                                title: 'directives/',
                                children: [
                                    {
                                        title: 'bind.js',
                                    },
                                    {
                                        title: 'for.js',
                                    },
                                    {
                                        title: 'html.js',
                                    },
                                    {
                                        title: 'if.js',
                                    },
                                    {
                                        title: 'model.js',
                                    },
                                    {
                                        title: 'on.js',
                                    },
                                    {
                                        title: 'show.js',
                                    },
                                    {
                                        title: 'text.js',
                                    },
                                ],
                            },
                            {
                                title: 'index.js',
                            },
                            {
                                title: 'observable.js',
                            },
                            {
                                title: 'polyfills.js',
                            },
                            {
                                title: 'utils.js',
                            },
                        ],
                    },
                    {
                        title: 'test/',
                        children: [
                            {
                                title: 'bind.spec.js',
                            },
                            {
                                title: 'cloak.spec.js',
                            },
                            {
                                title: 'constructor.spec.js',
                            },
                            {
                                title: 'custom-magic-properties.spec.js',
                            },
                            {
                                title: 'data.spec.js',
                            },
                            {
                                title: 'debounce.spec.js',
                            },
                            {
                                title: 'dispatch.spec.js',
                            },
                            {
                                title: 'el.spec.js',
                            },
                            {
                                title: 'for.spec.js',
                            },
                            {
                                title: 'html.spec.js',
                            },
                            {
                                title: 'if.spec.js',
                            },
                            {
                                title: 'lifecycle.spec.js',
                            },
                            {
                                title: 'model.spec.js',
                            },
                            {
                                title: 'mutations.spec.js',
                            },
                            {
                                title: 'nesting.spec.js',
                            },
                            {
                                title: 'next-tick.spec.js',
                            },
                            {
                                title: 'on.spec.js',
                            },
                            {
                                title: 'readonly.spec.js',
                            },
                            {
                                title: 'ref.spec.js',
                            },
                            {
                                title: 'show.spec.js',
                            },
                            {
                                title: 'spread.spec.js',
                            },
                            {
                                title: 'strict-mode.spec.js',
                            },
                            {
                                title: 'text.spec.js',
                            },
                            {
                                title: 'transition.spec.js',
                            },
                            {
                                title: 'utils.spec.js',
                            },
                            {
                                title: 'version.spec.js',
                            },
                            {
                                title: 'watch.spec.js',
                            },
                        ],
                    },
                ],
            },
        ],
        renderLevel: function(obj,i){
            let ref = 'l'+Math.random().toString(36).substring(7);
            let html = `<a href="#" class="block px-5 py-1 hover:text-gray-900" :class="{'has-children':level.children}" x-html="(level.children?'<i class=\\'mdi mdi-folder-outline text-orange-500\\'></i>':'<i class=\\'mdi mdi-file-outline text-gray-600\\'></i>')+' '+level.title" ${obj.children?`@click.prevent="toggleLevel($refs.${ref})"`:''}></a>`;

            if(obj.children) {
                html += `<ul style="display:none;" x-ref="${ref}" class="pl-5 pb-1 transition-all duration-1000 opacity-0">
                        <template x-for='(level,i) in level.children'>
                            <li x-html="renderLevel(level,i)"></li>
                        </template>
                    </ul>`;
            }

            return html;
        },
        showLevel: function(el) {
            if (el.style.length === 1 && el.style.display === 'none') {
                el.removeAttribute('style')
            } else {
                el.style.removeProperty('display')
            }
            setTimeout(()=>{
                el.previousElementSibling.querySelector('i.mdi').classList.add("mdi-folder-open-outline");
                el.previousElementSibling.querySelector('i.mdi').classList.remove("mdi-folder-outline");
                el.classList.add("opacity-100");
            },10)
        },
        hideLevel: function(el) {
            el.style.display = 'none';
            el.classList.remove("opacity-100");
            el.previousElementSibling.querySelector('i.mdi').classList.remove("mdi-folder-open-outline");
            el.previousElementSibling.querySelector('i.mdi').classList.add("mdi-folder-outline");

            let refs = el.querySelectorAll('ul[x-ref]');
            for (var i = 0; i < refs.length; i++) {
                this.hideLevel(refs[i]);
            }
        },
        toggleLevel: function(el) {
            if( el.style.length && el.style.display === 'none' ) {
                this.showLevel(el);
            } else {
                this.hideLevel(el);
            }
        }
    }
}