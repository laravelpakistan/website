<div>
    @if (Auth::guest())
        <div class="flex items-center gap-x-2">
            <x-heroicon-o-hand-thumb-up class="w-6 h-6" />
            
            <span class="font-medium">
                {{ count($this->thread->likes()) }}
            </span>
        </div>
    @else 
        <button type="button" wire:click="toggleLike" class="flex items-center gap-x-2 text-lio-500">
            <x-heroicon-o-hand-thumb-up class="w-6 h-6" />
            
            <span class="font-medium">
                {{ count($this->thread->likes()) }}
            </span>
            
            @if ($this->thread->isLikedBy(Auth::user()))
                <span class="text-gray-400 text-sm italic ml-1">You liked this thread</span>
            @endif
        </button>
    @endif
</div>
