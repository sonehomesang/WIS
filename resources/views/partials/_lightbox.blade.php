{{-- Global image lightbox. Open from anywhere with:
       @click="$dispatch('open-lightbox', { src: '{{ $url }}' })"
     and give the thumbnail `cursor-zoom-in`. Closes on backdrop click / ✕ / Esc. --}}
<div x-data="{ open: false, src: '' }"
     x-on:open-lightbox.window="src = $event.detail.src; open = true; document.body.classList.add('overflow-hidden')"
     x-on:keydown.escape.window="open = false; document.body.classList.remove('overflow-hidden')"
     x-show="open" x-cloak
     @click="open = false; document.body.classList.remove('overflow-hidden')"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 p-4 cursor-zoom-out">
    <img :src="src" alt="" @click.stop class="max-w-full max-h-[92vh] rounded-lg shadow-2xl object-contain select-none" />
    <button type="button" @click="open = false; document.body.classList.remove('overflow-hidden')"
            class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/90 hover:bg-white text-gray-700 text-xl leading-none shadow-lg flex items-center justify-center">✕</button>
</div>
